from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware  # Add this import
from api.routes import router

# Define origins before FastAPI initialization
origins = [
    "http://localhost:8080",
    "http://localhost:8081",
    "http://localhost:8082",
    "http://192.168.139.67:8082"
]

app = FastAPI(
    title="Chess360",
    description="Chess 360, a wider view of chess",
    version="0.1.0",
    openapi_tags=[
        {
            "name": "Chess",
            "description": "Chess 360, a wider view of chess",
        },
    ],
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(router, prefix="/chess")

@app.get("/", tags=["Root"])
async def root():
    """Root endpoint."""
    return {"message": "Welcome to Chess 360!"}