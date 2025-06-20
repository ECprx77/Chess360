import socketio
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from api.routes import router
from api.socket_manager import sio  # Import the configured sio instance

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

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8080"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Mount Socket.IO app using the imported sio instance
socket_app = socketio.ASGIApp(sio, app)

# Include routes
app.include_router(router, prefix="/chess")

@app.get("/", tags=["Root"])
async def root():
    """Root endpoint."""
    return {"message": "Welcome to Chess 360!"}

# Export the socket app
app = socket_app