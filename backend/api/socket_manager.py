import socketio
from typing import Dict, Any, cast
import chess
from .db_sync import update_game_state
import mysql.connector
from mysql.connector import Error
import random

"""
Real-time Game Communication Manager
Handles Socket.IO connections for live chess games, including move validation,
game state synchronization, and player management.
"""

sio = socketio.AsyncServer(
    async_mode='asgi',
    cors_allowed_origins=['http://localhost:8080']
)

# In-memory game state storage
games: Dict[str, chess.Board] = {}  # game_id -> chess board instance
player_games: Dict[str, str] = {}  # socket_id -> game_id
game_players: Dict[str, Dict[str, str]] = {}  # game_id -> {'white': socket_id, 'black': socket_id}

def get_db_connection():
    """Create and return a MySQL database connection."""
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='chess360'
    )

def get_game_players_from_db(game_id: int) -> Dict[str, int] | None:
    """
    Retrieve player IDs for a specific game from the database.
    
    Args:
        game_id (int): The game ID to look up
        
    Returns:
        Dict with white_player_id and black_player_id, or None if not found
    """
    try:
        connection = get_db_connection()
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT white_player_id, black_player_id FROM games WHERE id = %s", (game_id,))
        players = cast(Dict[str, Any], cursor.fetchone())
        if players:
            return {
                'white_player_id': int(players.get('white_player_id', 0)),
                'black_player_id': int(players.get('black_player_id', 0))
            }
        return None
    except Error as e:
        print(f"Database error in get_game_players_from_db: {e}")
        return None
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

def get_active_game(game_id: int):
    """
    Retrieve active game information from the database.
    
    Args:
        game_id (int): The game ID to look up
        
    Returns:
        Game data dictionary or None if not found
    """
    try:
        connection = get_db_connection()
        cursor = connection.cursor(dictionary=True)
        
        query = """
            SELECT g.*, ag.game_status 
            FROM games g
            LEFT JOIN active_games ag ON g.id = ag.game_id
            WHERE g.id = %s AND g.status = 'ongoing'
        """
        cursor.execute(query, (game_id,))
        return cursor.fetchone()
        
    except Error as e:
        print(f"Database error: {e}")
        return None
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()

@sio.event
async def connect(sid, environ):
    """Handle new client connection."""
    print(f"Client connected: {sid}")

@sio.event
async def disconnect(sid):
    """Handle client disconnection and cleanup game state."""
    if sid in player_games:
        game_id = player_games[sid]
        
        # Clean up in-memory game state
        if game_id in games:
            del games[game_id]
        if game_id in game_players:
            del game_players[game_id]
        del player_games[sid]
        
        print(f"Player disconnected: {sid}")

@sio.event
async def join_game(sid, data):
    """
    Handle player joining a game room.
    
    Args:
        sid: Socket ID of the connecting player
        data: Dictionary containing gameId and color
    """
    if not data or 'gameId' not in data or 'color' not in data:
        print(f"Invalid join_game data from {sid}")
        return
        
    game_id = str(data['gameId'])
    color = data['color']
    socket_room = f"game_{game_id}"
    
    try:
        connection = get_db_connection()
        cursor = connection.cursor(dictionary=True)
        
        # Ensure active game entry exists
        cursor.execute("""
            SELECT * FROM active_games WHERE game_id = %s
        """, (game_id,))
        
        active_game = cursor.fetchone()
        if not active_game:
            # Create new active game entry
            cursor.execute("""
                INSERT INTO active_games 
                (game_id, socket_room, current_turn, game_status) 
                VALUES (%s, %s, 'white', 'active')
            """, (game_id, socket_room))
            connection.commit()
        
        # Retrieve current game state
        cursor.execute("""
            SELECT g.*, ag.current_turn, g.current_position 
            FROM games g
            JOIN active_games ag ON g.id = ag.game_id
            WHERE g.id = %s
        """, (game_id,))
        
        game_data = cast(Dict[str, Any], cursor.fetchone())
        
        if game_data:
            current_position = game_data.get('current_position')
            if isinstance(current_position, str):
                if game_id not in games:
                    board = chess.Board(current_position)
                    games[game_id] = board
            
            if game_id not in game_players:
                game_players[game_id] = {}
            
            # Register player in game
            game_players[game_id][color] = sid
            player_games[sid] = game_id
            
            # Add player to game room
            await sio.enter_room(sid, socket_room)
            
            is_white_turn = game_data.get('current_turn') == 'white'
            
            # Send current game state to player
            await sio.emit('game_joined', {
                'game_id': game_id,
                'color': color,
                'fen': current_position,
                'is_white_turn': is_white_turn
            }, room=socket_room)
            
            print(f"Player joined: color={color}, is_white_turn={is_white_turn}")
            
    except Error as e:
        print(f"Database error in join_game: {e}")
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()

@sio.event
async def get_legal_moves(sid, data):
    """
    Provide legal moves for a specific piece position.
    
    Args:
        sid: Socket ID of the requesting player
        data: Dictionary containing square coordinates
    """
    try:
        game_id = player_games.get(sid)
        if not game_id or game_id not in games:
            print(f"Game not found for socket {sid}")
            await sio.emit('legal_moves', {'legal_moves': [], 'error': 'Game not found'}, room=sid)
            return

        board = games[game_id]
        square = chess.parse_square(data['square'])
        
        # Verify it's the player's turn
        is_white_player = game_players[game_id].get('white') == sid
        
        if board.turn == chess.WHITE != is_white_player:
            print(f"Not {('white' if is_white_player else 'black')}'s turn")
            await sio.emit('legal_moves', {'legal_moves': [], 'error': 'Not your turn'}, room=sid)
            return
        
        legal_moves = [move.uci() for move in board.legal_moves if move.from_square == square]
        print(f"Legal moves for {data['square']}: {legal_moves}")
        await sio.emit('legal_moves', {'legal_moves': legal_moves, 'status': 'ok'}, room=sid)
        
    except Exception as e:
        print(f"Error in get_legal_moves: {e}")
        await sio.emit('legal_moves', {'legal_moves': [], 'error': str(e)}, room=sid)

@sio.event
async def make_move(sid, data):
    """
    Process and validate a chess move, update game state, and check for game over conditions.
    
    Args:
        sid: Socket ID of the player making the move
        data: Dictionary containing the move in UCI format
        
    Returns:
        Dict: Status of the move operation
    """
    try:
        game_id = player_games.get(sid)
        if not game_id or game_id not in games:
            print(f"Game not found for socket {sid}")
            return {'error': 'Game not found'}
        
        board = games[game_id]
        move = chess.Move.from_uci(data['move'])
        is_white_player = game_players[game_id].get('white') == sid
        
        print(f"Move attempt: {data['move']} by {'white' if is_white_player else 'black'}")
        
        # Verify it's the player's turn
        if board.turn == chess.WHITE != is_white_player:
            print(f"Wrong turn: {'white' if board.turn else 'black'} to move")
            return {'error': 'Not your turn'}
        
        # Validate and execute the move
        if move in board.legal_moves:
            board.push(move)
            new_fen = board.fen()
            print(f"Valid move made: {data['move']}, new position: {new_fen}")
            
            try:
                # Update database with new game state
                update_game_state(int(game_id), new_fen, data['move'])
                
                socket_room = f"game_{game_id}"
                # Broadcast move to all players in the game
                await sio.emit('move_made', {
                    'fen': new_fen, 
                    'is_white_turn': board.turn == chess.WHITE
                }, room=socket_room)

                # Check for game termination conditions
                if board.is_game_over():
                    status = ''
                    winner_id = None
                    
                    players = get_game_players_from_db(int(game_id))

                    if players:
                        if board.is_checkmate():
                            status = 'completed'
                            # Winner is the opposite color of current turn
                            winner_id = players['white_player_id'] if board.turn == chess.BLACK else players['black_player_id']
                        elif board.is_stalemate() or board.is_insufficient_material() or board.is_seventyfive_moves() or board.is_fivefold_repetition():
                            status = 'draw'

                        if status:
                            # Notify all players of game end
                            await sio.emit('game_over', {
                                'status': status,
                                'winnerId': winner_id,
                                'gameId': game_id
                            }, room=socket_room)
                            # Clean up in-memory game state
                            if game_id in games:
                                del games[game_id]
                            if game_id in game_players:
                                del game_players[game_id]

                return {'status': 'ok'}
            
            except Exception as e:
                print(f"Error updating game state or emitting move: {e}")
                return {'error': 'Failed to update game state'}

        else:
            print(f"Illegal move: {data['move']}")
            return {'error': 'Illegal move'}

    except ValueError:
        print(f"Invalid move format: {data.get('move')}")
        return {'error': 'Invalid move format'}
    except Exception as e:
        print(f"An unexpected error occurred in make_move: {e}")
        return {'error': 'An internal server error occurred'}