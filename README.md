# Chess360 - Fisher Random Chess Platform

Chess360 is a modern web-based chess platform that implements Fischer Random Chess (also known as Chess960), featuring real-time multiplayer gameplay, user authentication, friend systems, and match history tracking.

## Features

- **Fischer Random Chess**: Play chess with randomized starting positions while maintaining castling legality
- **Real-time Multiplayer**: Live chess games with Socket.IO for instant move synchronization
- **User Authentication**: Secure login and registration system with ELO rating tracking
- **Friend System**: Add friends, send/receive friend requests, and invite friends to games
- **Match History**: View detailed match history with results, opponents, and ELO changes
- **Modern UI**: Desktop-style interface with draggable elements and window-based modals
- **Responsive Design**: Works on desktop and tablet devices

## Technology Stack

### Backend
- **Python**: FastAPI with Socket.IO for real-time communication
- **PHP**: REST API endpoints for user management and game data
- **MySQL**: Database for user accounts, games, and match history
- **Chess Engine**: Python-chess library for game logic and move validation

### Frontend
- **Vue.js 3**: Modern reactive frontend framework
- **Socket.IO Client**: Real-time communication with game server
- **CSS3**: Custom styling with modern design patterns

## Prerequisites

Before setting up Chess360, ensure you have the following installed:

- **Python 3.8+** with pip
- **Node.js 14+** with npm
- **XAMPP** (recommended) or any local web server (Apache + PHP + MySQL)
- **MySQL** database server

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd Chess360
```

### 2. Database Setup

1. **Start XAMPP** and ensure Apache and MySQL services are running
2. **Create Database**: Open phpMyAdmin (http://localhost/phpmyadmin) and create a new database named `chess360`
3. **Import Schema**: Import the database schema from `backend/chess360.sql`

### 3. Backend Setup

#### Python Backend

1. **Navigate to backend directory**:
   ```bash
   cd backend
   ```

2. **Create virtual environment**:
   ```bash
   python -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

3. **Install Python dependencies**:
   ```bash
   pip install -r requirements.txt
   ```

4. **Configure Database Connection**:
   
   Update the database connection in the following files with your MySQL credentials:
   
   - `backend/api/socket_manager.py` (lines 25-30)
   - `backend/api/db_sync.py` (lines 15-20)
   
   ```python
   # Replace with your MySQL credentials
   connection = mysql.connector.connect(
       host='localhost',
       user='your_username',
       password='your_password',
       database='chess360'
   )
   ```

5. **Start Python Backend**:
   ```bash
   python main.py
   ```
   
   The backend will start on `http://localhost:8000`

#### PHP Backend

1. **Copy PHP files to web server**:
   - Copy the entire `backend/php/` folder to your XAMPP `htdocs` directory
   - Example: `C:\xampp\htdocs\chess360\php\`

2. **Configure Database Connection**:
   
   Update `backend/php/config.php` with your MySQL credentials:
   
   ```php
   $host = 'localhost';
   $db   = 'chess360';
   $user = 'your_username';  // Replace with your MySQL username
   $pass = 'your_password';  // Replace with your MySQL password
   $charset = 'utf8mb4';
   ```

3. **Verify PHP Setup**:
   - Access `http://localhost/chess360/php/config.php` to test database connection
   - You should see a JSON response if the connection is successful

### 4. Frontend Setup

1. **Navigate to frontend directory**:
   ```bash
   cd frontend
   ```

2. **Install Node.js dependencies**:
   ```bash
   npm install
   ```

3. **Start Development Server**:
   ```bash
   npm run serve
   ```
   
   The frontend will start on `http://localhost:8080`

## Configuration Files

### Database Credentials

You need to update database credentials in these files:

1. **`backend/api/socket_manager.py`** (lines 25-30)
2. **`backend/api/db_sync.py`** (lines 15-20)  
3. **`backend/php/config.php`** (lines 18-22)

### Default Credentials

If you're using XAMPP with default settings:
- **Username**: `root`
- **Password**: `` (empty password)
- **Host**: `localhost`
- **Database**: `chess360`

## Usage

### Starting the Application

1. **Start XAMPP** (Apache + MySQL)
2. **Start Python Backend**:
   ```bash
   cd backend
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   python main.py
   uvicorn main:app --host 0.0.0.0 --port 8000 --reload
   ```
3. **Start Frontend**:
   ```bash
   cd frontend
   npm run serve
   ```

### Playing Chess

1. **Register/Login**: Create an account or log in at `http://localhost:8080`
2. **Navigate to Hub**: After login, you'll be taken to the main hub
3. **Start Game**: Click the Chess360 icon to begin matchmaking
4. **Play**: Once matched with an opponent, the chess board will appear
5. **Move Pieces**: Click and drag pieces to make moves
6. **View History**: Access match history and friend management through the navigation bar

## Project Structure

```
Chess360/
├── backend/
│   ├── api/
│   │   ├── socket_manager.py    # Real-time game communication
│   │   ├── routes.py           # REST API endpoints
│   │   └── db_sync.py          # Database synchronization
│   ├── php/                    # PHP backend files (copy to htdocs)
│   │   ├── config.php          # Database configuration
│   │   ├── login.php           # User authentication
│   │   ├── register.php        # User registration
│   │   └── ...                 # Other API endpoints
│   ├── ChessGame.py            # Chess game logic
│   ├── main.py                 # Python server entry point
│   └── chess360.sql            # Database schema
├── frontend/
│   ├── src/
│   │   ├── components/         # Vue.js components
│   │   ├── views/              # Page components
│   │   └── composables/        # Reusable logic
│   └── public/                 # Static assets
└── img/                        # Application images
```

## API Endpoints

### Authentication
- `POST /php/login.php` - User login
- `POST /php/register.php` - User registration

### Game Management
- `POST /php/joinQueue.php` - Join matchmaking queue
- `POST /php/checkMatch.php` - Check for available matches
- `POST /php/leaveQueue.php` - Leave matchmaking queue
- `POST /php/endGame.php` - End game and update statistics

### Social Features
- `POST /php/searchUser.php` - Search for users
- `POST /php/sendFriendRequest.php` - Send friend request
- `POST /php/getFriends.php` - Get friends list
- `POST /php/getMatchHistory.php` - Get match history

### Common Issues

1. **Database Connection Failed**:
   - Verify XAMPP MySQL service is running
   - Check database credentials in config files
   - Ensure database `chess360` exists

2. **PHP Files Not Found**:
   - Verify PHP files are in correct htdocs directory
   - Check Apache service is running in XAMPP
   - Test with `http://localhost/chess360/php/config.php`

3. **Socket.IO Connection Failed**:
   - Ensure Python backend is running on port 8000
   - Check firewall settings
   - Verify CORS configuration

4. **Frontend Build Errors**:
   - Run `npm install` to install dependencies
   - Check Node.js version (requires 14+)
   - Clear npm cache: `npm cache clean --force`

### Port Conflicts

If you encounter port conflicts:
- **Frontend**: Change port in `frontend/vue.config.js`
- **Backend**: Change port in `backend/main.py`
- **Database**: Change port in database config files

## Development

### Adding New Features

1. **Backend API**: Add new endpoints in `backend/php/` or `backend/api/routes.py`
2. **Frontend Components**: Create new Vue components in `frontend/src/components/`
3. **Database**: Add new tables/columns and update `chess360.sql`

### Code Style

- **Python**: Follow PEP 8 guidelines
- **JavaScript/Vue**: Use ESLint configuration
- **PHP**: Follow PSR-12 standards
- **CSS**: Use BEM methodology for class naming

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Support

For issues and questions:
1. Check the troubleshooting section above
2. Review the code documentation
3. Create an issue in the repository

---

**Note**: This is a development project. For production deployment, ensure proper security measures, environment variables for sensitive data, and HTTPS configuration.
