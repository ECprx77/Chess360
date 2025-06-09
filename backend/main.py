from fastapi import FastAPI
from api.routes import router

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

app.include_router(router)

@app.get("/", tags=["Root"])
async def root():
    """Root endpoint."""
    return {"message": "Welcome to Chess 360!"}