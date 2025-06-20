import mysql.connector
from mysql.connector import Error

def update_game_state(game_id: int, new_position: str, move: str):
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='chess360'
        )
        cursor = connection.cursor()
        
        # Update both current_position and moves_history
        query = """
            UPDATE games 
            SET current_position = %s,
                moves_history = CONCAT(COALESCE(moves_history, ''), %s, ' ')
            WHERE id = %s
        """
        cursor.execute(query, (new_position, move, game_id))
        connection.commit()
        print(f"Game state updated successfully for game {game_id}")
        
    except Error as e:
        print(f"Error updating game state: {e}")
        raise e
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()