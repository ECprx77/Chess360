import ChessGame

from fastapi import FastAPI


__name__ = "Chess360"
__version__ = "0.1.0"
app = FastAPI(
    title=__name__,
    description= "Chess 360, a wider view of chess",
    version=__version__,
    openapi_tags=[
        {
            "name": "Chess",
            "description": "Chess 360, a wider view of chess",
        },
    ],
    docs_url="/docs",
    redoc_url="/redoc",
    openapi_url="/openapi.json",
    root_path="/api",
    root_path_in_servers=False,

    root_path_outside_servers=False,
)
@app.get("/", tags=["Chess"])
async def root():
    """
    Root endpoint.
    """
    return {"message": "Welcome to Chess 360!"}

@app.get("/chess", tags=["Chess"])
async def chess():
    """
    Chess endpoint.
    """
    return {"message": "Welcome to Chess 360!"}

@app.get("/chess/game", tags=["Chess"])
async def chess_game():
    """
    Chess game endpoint.
    """
    return {"message": "Welcome to Chess 360!"}

@app.get("/chess/game/start", tags=["Chess"])
async def chess_game_start():
    """
    Start a new chess game.
    """
    game = ChessGame.ChessGame()
    game.start_game()
    return {"message": "New chess game started!"}

@app.get("/chess/game/move", tags=["Chess"])
async def chess_game_move():
    """
    Make a move in the chess game.
    """
    game = ChessGame.ChessGame()
    game.make_move()
    return {"message": "Move made in chess game!"}


@app.get("/chess/game/end", tags=["Chess"])
async def chess_game_end():
    """
    End the chess game.
    """
    game = ChessGame.ChessGame()
    game.end_game()
    return {"message": "Chess game ended!"}

@app.get("/chess/game/status", tags=["Chess"])
async def chess_game_status():
    """
    Get the status of the chess game.
    """
    game = ChessGame.ChessGame()
    status = game.get_status()
    return {"status": status}

@app.get("/chess/game/board", tags=["Chess"])
async def chess_game_board():
    """
    Get the chess board.
    """
    game = ChessGame.ChessGame()
    board = game.get_board()
    return {"board": board}

@app.get("/chess/game/players", tags=["Chess"])
async def chess_game_players():
    """
    Get the players in the chess game.
    """
    game = ChessGame.ChessGame()
    players = game.get_players()
    return {"players": players}

@app.get("/chess/game/history", tags=["Chess"])
async def chess_game_history():
    """
    Get the history of the chess game.
    """
    game = ChessGame.ChessGame()
    history = game.get_history()
    return {"history": history}

@app.get("/chess/game/undo", tags=["Chess"])
async def chess_game_undo():
    """
    Undo the last move in the chess game.
    """
    game = ChessGame.ChessGame()
    game.undo_move()
    return {"message": "Last move undone in chess game!"}

@app.get("/chess/game/save", tags=["Chess"])
async def chess_game_save():
    """
    Save the chess game.
    """
    game = ChessGame.ChessGame()
    game.save_game()
    return {"message": "Chess game saved!"}

@app.get("/chess/game/load", tags=["Chess"])
async def chess_game_load():
    """
    Load a saved chess game.
    """
    game = ChessGame.ChessGame()
    game.load_game()
    return {"message": "Chess game loaded!"}

@app.get("/chess/game/resign", tags=["Chess"])
async def chess_game_resign():
    """
    Resign from the chess game.
    """
    game = ChessGame.ChessGame()
    game.resign_game()
    return {"message": "Resigned from chess game!"}

@app.get("/chess/game/offer_rematch", tags=["Chess"])
async def chess_game_offer_rematch():
    """
    Offer a rematch in the chess game.
    """
    game = ChessGame.ChessGame()
    game.offer_rematch()
    return {"message": "Offered a rematch in chess game!"}

@app.get("/chess/game/offer_draw", tags=["Chess"])
async def chess_game_offer_draw():
    """
    Offer a draw in the chess game.
    """
    game = ChessGame.ChessGame()
    game.offer_draw()
    return {"message": "Offered a draw in chess game!"}

@app.get("/chess/game/forfeit", tags=["Chess"])
async def chess_game_forfeit():
    """
    Forfeit the chess game.
    """
    game = ChessGame.ChessGame()
    game.forfeit_game()
    return {"message": "Forfeited chess game!"}