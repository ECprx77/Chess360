from fastapi import APIRouter, HTTPException
from typing import Dict, Any
from pydantic import BaseModel
from ChessGame import ChessGame

router = APIRouter(
    prefix="/chess",
    tags=["Chess"]
)

class MoveRequest(BaseModel):
    move: str

# Create a global game instance
game = ChessGame()

@router.get("/")
async def chess_root() -> Dict[str, str]:
    """Chess endpoint."""
    return {"message": "Welcome to Chess 360!"}

@router.get("/game")
async def get_game() -> Dict[str, str]:
    """Chess game endpoint."""
    return {"message": "Welcome to Chess 360!"}

@router.post("/game/start")
async def start_game() -> Dict[str, Any]:
    """Start a new chess game."""
    return game.reset_game()

@router.post("/game/move")
async def make_move(move_request: MoveRequest) -> Dict[str, Any]:
    """Make a move in the chess game."""
    result = game.make_move(move_request.move)
    if "error" in result:
        raise HTTPException(status_code=400, detail=result["error"])
    return result

@router.get("/game/status")
async def get_status() -> Dict[str, Any]:
    """Get the status of the chess game."""
    status = game.get_status()
    return {"status": status}

@router.get("/game/board")
async def get_board() -> Dict[str, Any]:
    """Get the chess board."""
    return {"board": game._get_game_state()}

@router.get("/game/players")
async def get_players() -> Dict[str, Any]:
    """Get the players in the chess game."""
    players = game.get_players()
    return {"players": players}

@router.get("/game/history")
async def get_history() -> Dict[str, Any]:
    """Get the history of the chess game."""
    history = game.get_history()
    return {"history": history}

@router.post("/game/undo")
async def undo_move() -> Dict[str, str]:
    """Undo the last move in the chess game."""
    game.undo_move()
    return {"message": "Last move undone in chess game!"}

@router.post("/game/save")
async def save_game() -> Dict[str, str]:
    """Save the chess game."""
    game.save_game()
    return {"message": "Chess game saved!"}

@router.post("/game/load")
async def load_game() -> Dict[str, str]:
    """Load a saved chess game."""
    game.load_game()
    return {"message": "Chess game loaded!"}

@router.post("/game/resign")
async def resign_game() -> Dict[str, str]:
    """Resign from the chess game."""
    game.resign_game()
    return {"message": "Resigned from chess game!"}

@router.post("/game/offer-rematch")
async def offer_rematch() -> Dict[str, str]:
    """Offer a rematch in the chess game."""
    game.offer_rematch()
    return {"message": "Offered a rematch in chess game!"}

@router.post("/game/offer-draw")
async def offer_draw() -> Dict[str, str]:
    """Offer a draw in the chess game."""
    game.offer_draw()
    return {"message": "Offered a draw in chess game!"}

@router.post("/game/forfeit")
async def forfeit_game() -> Dict[str, str]:
    """Forfeit the chess game."""
    game.forfeit_game()
    return {"message": "Forfeited chess game!"}