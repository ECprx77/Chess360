import socketio
from typing import Dict
import chess
from .db_sync import update_game_state
import mysql.connector
from mysql.connector import Error
import random

sio = socketio.AsyncServer(
    async_mode='asgi',
    cors_allowed_origins=['http://localhost:8080']
)

# Store game states
games: Dict[str, chess.Board] = {}
# Store player connections
player_games: Dict[str, str] = {}  # socket_id -> game_id
game_players: Dict[str, Dict[str, str]] = {}  # game_id -> {'white': socket_id, 'black': socket_id}

def get_active_game(game_id: int):
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='chess360'
        )
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
    print(f"Client connected: {sid}")

@sio.event
async def disconnect(sid):
    if sid in player_games:
        game_id = player_games[sid]
        
        # Clean up memory only
        if game_id in games:
            del games[game_id]
        if game_id in game_players:
            del game_players[game_id]
        del player_games[sid]
        
        print(f"Player disconnected: {sid}")

@sio.event
async def join_game(sid, data):
    if not data or 'gameId' not in data or 'color' not in data:
        print(f"Invalid join_game data from {sid}")
        return
        
    game_id = str(data['gameId'])
    color = data['color']
    socket_room = f"game_{game_id}"  # Define socket_room here
    
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='chess360'
        )
        cursor = connection.cursor(dictionary=True)
        
        # First get or create active game entry
        cursor.execute("""
            SELECT * FROM active_games WHERE game_id = %s
        """, (game_id,))
        
        active_game = cursor.fetchone()
        if not active_game:
            # Create new active game with white's turn
            cursor.execute("""
                INSERT INTO active_games 
                (game_id, socket_room, current_turn, game_status) 
                VALUES (%s, %s, 'white', 'active')
            """, (game_id, socket_room))
            connection.commit()
        
        # Get game state including current_position
        cursor.execute("""
            SELECT g.*, ag.current_turn, g.current_position 
            FROM games g
            JOIN active_games ag ON g.id = ag.game_id
            WHERE g.id = %s
        """, (game_id,))
        
        game_data = cursor.fetchone()
        
        if game_data:
            if game_id not in games:
                board = chess.Board(game_data['current_position'])
                games[game_id] = board
            
            if game_id not in game_players:
                game_players[game_id] = {}
            
            # Store player connection in memory only
            game_players[game_id][color] = sid
            player_games[sid] = game_id
            
            # Add player to socket room
            await sio.enter_room(sid, socket_room)
            
            # Define is_white_turn from current_turn
            is_white_turn = game_data['current_turn'] == 'white'
            
            # Send current position from database
            await sio.emit('game_joined', {
                'game_id': game_id,
                'color': color,
                'fen': game_data['current_position'],
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
async def get_legal_moves(sid, data):  # Remove callback parameter
    try:
        game_id = player_games.get(sid)
        if not game_id or game_id not in games:
            print(f"Game not found for socket {sid}")
            await sio.emit('legal_moves', {'legal_moves': [], 'error': 'Game not found'}, room=sid)
            return

        board = games[game_id]
        square = chess.parse_square(data['square'])
        
        # Check if it's player's turn
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
    try:
        game_id = player_games.get(sid)
        if not game_id or game_id not in games:
            print(f"Game not found for socket {sid}")
            return {'error': 'Game not found'}
        
        board = games[game_id]
        move = chess.Move.from_uci(data['move'])
        is_white_player = game_players[game_id].get('white') == sid
        
        print(f"Move attempt: {data['move']} by {'white' if is_white_player else 'black'}")
        
        if board.turn == chess.WHITE != is_white_player:
            print(f"Wrong turn: {'white' if board.turn else 'black'} to move")
            return {'error': 'Not your turn'}
        
        if move in board.legal_moves:
            board.push(move)
            new_fen = board.fen()
            print(f"Valid move made: {data['move']}, new position: {new_fen}")
            
            try:

                update_game_state(int(game_id), new_fen, data['move'])
                
                # Emit to both players immediately
                socket_room = f"game_{game_id}"
                move_data = {
                    'move': move.uci(),
                    'fen': new_fen,
                    'is_white_turn': board.turn == chess.WHITE
                }
                await sio.emit('move_made', move_data, room=socket_room)
                
                return {'success': True}
                
            except Exception as e:
                print(f"Failed to update game state: {e}")
                return {'error': 'Failed to update game state'}
        else:
            print(f"Illegal move attempted: {data['move']}")
            return {'error': 'Illegal move'}
            
    except Exception as e:
        print(f"Error in make_move: {e}")
        return {'error': str(e)}