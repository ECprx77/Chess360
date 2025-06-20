import socketio
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from api.routes import router
from api.socket_manager import sio

"""
Chess360 Backend Server
Combines FastAPI for REST endpoints and Socket.IO for real-time game communication.
"""

# Create FastAPI app
app = FastAPI(
    title="Chess360",
    description="Chess 360, a wider view of chess",
    version="0.1.0",
    openapi_tags=[{
        "name": "Chess",
        "description": "Chess 360, a wider view of chess",
    }],
)

# Configure CORS to allow frontend communication
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8080"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Mount Socket.IO for real-time game events
socket_app = socketio.ASGIApp(sio, app)

# Include REST API routes
app.include_router(router, prefix="/chess")

@app.get("/", tags=["Root"])
async def root():
    """Health check endpoint."""
    return {"message": "Welcome to Chess 360!"}

# Export the combined Socket.IO and FastAPI application
app = socket_app