from fastapi import APIRouter, HTTPException
from typing import Dict, Any
from pydantic import BaseModel
from ChessGame import ChessGame
import chess
import random

"""
REST API Routes for Chess360
Provides endpoints for game management, move validation, and game state retrieval.
"""

router = APIRouter()

class MoveRequest(BaseModel):
    """Request model for chess moves in UCI format."""
    move: str

class SquareRequest(BaseModel):
    """Request model for square coordinates in algebraic notation."""
    square: str  # e.g. "e2" in algebraic notation

class GameStartRequest(BaseModel):
    """Request model for starting a new game with specified variant."""
    variant: str = "standard"  # default to standard chess

# Global game instance for single-player mode
game = ChessGame()

# In-memory storage for active games
active_games = {}

@router.get("/")
async def chess_root() -> Dict[str, str]:
    """Root endpoint for chess API."""
    return {"message": "Welcome to Chess 360!"}

@router.get("/game")
async def get_game() -> Dict[str, str]:
    """Game information endpoint."""
    return {"message": "Welcome to Chess 360!"}

@router.post("/game/start")
@router.get("/game/start")
async def start_game(game_request: GameStartRequest | None = None) -> Dict[str, Any]:
    """
    Start a new chess game with specified variant.
    
    Args:
        game_request: Optional game configuration
        
    Returns:
        Dict containing initial FEN position and game variant
    """
    variant = "chess960" if game_request and game_request.variant == "chess960" else "standard"
    
    if variant == "chess960":
        position_number = random.randint(0, 959)
        board = chess.Board.from_chess960_pos(position_number)
    else:
        board = chess.Board()
    
    return {
        "fen": board.fen(),
        "variant": variant,
        "status": "ok"
    }

@router.post("/game/move")
async def make_move(move_request: MoveRequest) -> Dict[str, Any]:
    """
    Execute a chess move and return updated game state.
    
    Args:
        move_request: Move in UCI format
        
    Returns:
        Dict containing game state after move or error
    """
    result = game.make_move(move_request.move)
    if "error" in result:
        raise HTTPException(status_code=400, detail=result["error"])
    return result

@router.get("/game/status")
async def get_status() -> Dict[str, Any]:
    """Get current game status and state information."""
    status = game._get_game_state()
    return {"status": status}

@router.get("/game/board")
async def get_board() -> Dict[str, Any]:
    """Get complete board state and game information."""
    return {"board": game._get_game_state()}

@router.get("/game/players")
async def get_players() -> Dict[str, Any]:
    """Get player information for the current game."""
    return {"players": "Not implemented"}

@router.get("/game/history")
async def get_history() -> Dict[str, Any]:
    """Get move history for the current game."""
    return {"history": game.move_history}

@router.post("/game/undo")
async def undo_move() -> Dict[str, str]:
    """Undo the last move in the game."""
    return {"message": "Undo not implemented"}

@router.post("/game/save")
async def save_game() -> Dict[str, str]:
    """Save the current game state."""
    return {"message": "Save not implemented"}

@router.post("/game/load")
async def load_game() -> Dict[str, str]:
    """Load a previously saved game."""
    return {"message": "Load not implemented"}

@router.post("/game/resign")
async def resign_game() -> Dict[str, str]:
    """Resign from the current game."""
    return {"message": "Resign not implemented"}

@router.post("/game/offer-rematch")
async def offer_rematch() -> Dict[str, str]:
    """Offer a rematch to the opponent."""
    return {"message": "Rematch not implemented"}

@router.post("/game/offer-draw")
async def offer_draw() -> Dict[str, str]:
    """Offer a draw to the opponent."""
    return {"message": "Draw offer not implemented"}

@router.post("/game/forfeit")
async def forfeit_game() -> Dict[str, str]:
    """Forfeit the current game."""
    return {"message": "Forfeit not implemented"}

@router.post("/game/legal-moves")
async def get_legal_moves_from(move_request: SquareRequest) -> Dict[str, Any]:
    """
    Get all legal moves for a piece on a specific square.
    
    Args:
        move_request: Square coordinates in algebraic notation
        
    Returns:
        Dict containing list of legal moves in UCI format
    """
    legal_moves = []
    try:
        square = chess.parse_square(move_request.square)
        for move in game.board.legal_moves:
            if move.from_square == square:
                legal_moves.append(move.uci())
        return {"legal_moves": legal_moves}
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid square format")