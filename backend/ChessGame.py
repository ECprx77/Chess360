import chess
import chess.engine
from typing import List, Optional, Dict
import random

class ChessGame:
    """
    Chess game engine that handles game logic, moves, and state management.
    Supports both standard chess and Chess960 variants.
    """
    
    def __init__(self, variant="standard"):
        """
        Initialize a new chess game.
        
        Args:
            variant (str): Game variant - "standard" or "chess960"
        """
        self.variant = variant
        if variant == "chess960":
            self.board = self._create_chess960_board()
        else:
            self.board = chess.Board()
        self.move_history: List[str] = []
        self.game_status: str = "active"
        
    def _create_chess960_board(self) -> chess.Board:
        """
        Creates a Chess960 starting position with randomized piece placement.
        Chess960 has 960 possible starting positions that maintain castling legality.
        """
        return chess.Board.from_chess960_pos(random.randint(0, 959))
    
    def make_move(self, move_uci: str) -> Dict:
        """
        Execute a move using Universal Chess Interface notation.
        
        Args:
            move_uci (str): Move in UCI format (e.g., "e2e4", "g1f3")
            
        Returns:
            Dict: Game state information or error message
        """
        try:
            move = chess.Move.from_uci(move_uci)
            if move in self.board.legal_moves:
                self.board.push(move)
                self.move_history.append(move_uci)
                return self._get_game_state()
            return {"error": "Illegal move"}
        except ValueError:
            return {"error": "Invalid move format"}

    def get_legal_moves(self) -> List[str]:
        """
        Get all legal moves available in the current position.
        
        Returns:
            List[str]: List of legal moves in UCI format
        """
        return [move.uci() for move in self.board.legal_moves]

    def _get_game_state(self) -> Dict:
        """
        Generate comprehensive game state information.
        
        Returns:
            Dict: Complete game state including FEN, legal moves, game status, etc.
        """
        state = {
            "fen": self.board.fen(),
            "legal_moves": self.get_legal_moves(),
            "move_history": self.move_history,
            "is_check": self.board.is_check(),
            "is_checkmate": self.board.is_checkmate(),
            "is_stalemate": self.board.is_stalemate(),
            "is_insufficient_material": self.board.is_insufficient_material(),
            "is_game_over": self.board.is_game_over(),
            "turn": "white" if self.board.turn else "black",
            "variant": self.variant
        }
        
        # Determine game result if game is over
        if self.board.is_game_over():
            if self.board.is_checkmate():
                # Checkmate: opposite color of current turn wins
                state["result"] = "0-1" if self.board.turn else "1-0"
            else:
                # Draw (stalemate, insufficient material, etc.)
                state["result"] = "1/2-1/2"
                
        return state

    def _create_engine(self) -> Optional[chess.engine.SimpleEngine]:
        """
        Initialize Stockfish chess engine for position evaluation.
        
        Returns:
            Optional[chess.engine.SimpleEngine]: Stockfish engine instance or None if failed
        """
        try:
            return chess.engine.SimpleEngine.popen_uci("stockfish")
        except Exception as e:
            raise RuntimeError(f"Failed to start Stockfish: {e}")

    # def get_position_evaluation(self) -> Dict[str, any]:
    #     """
    #     Evaluate the current position using Stockfish
    #     Returns detailed evaluation information
    #     """
    #     try:
    #         with self._create_engine() as engine:
    #             info = engine.analyse(
    #                 self.board,
    #                 chess.engine.Limit(depth=15),
    #                 multipv=3  # Get top 3 variations
    #             )
                
    #             eval_info = {
    #                 "score_pawns": None,
    #                 "best_moves": [],
    #                 "mate": None
    #             }
                
    #             if "score" in info:
    #                 score = info["score"].relative
    #                 if score.is_mate():
    #                     eval_info["mate"] = score.mate()
    #                 else:
    #                     eval_info["score_pawns"] = score.score() / 100  # Convert centipawns to pawns
                
    #             if "pv" in info:
    #                 eval_info["best_moves"] = [move.uci() for move in info["pv"][:3]]
                
    #             return eval_info
    #     except Exception as e:
    #         return {"error": str(e)}

    # def get_best_move(self, depth: int = 15) -> Dict[str, any]:
    #     """Get the best move with evaluation"""
    #     try:
    #         with self._create_engine() as engine:
    #             result = engine.play(
    #                 self.board,
    #                 chess.engine.Limit(depth=depth)
    #             )
    #             return {
    #                 "move": result.move.uci() if result.move else None,
    #                 "depth": depth
    #             }
    #     except Exception as e:
    #         return {"error": str(e)}

    def reset_game(self) -> Dict:
        """
        Reset the game to initial position, clearing move history.
        
        Returns:
            Dict: Initial game state
        """
        if self.variant == "chess960":
            self.board = self._create_chess960_board()
        else:
            self.board = chess.Board()
        self.move_history = []
        return self._get_game_state()