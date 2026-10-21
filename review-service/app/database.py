import os
from pymongo import ASCENDING, MongoClient

db = None

def init_db():
    global db
    client = MongoClient(
        os.getenv("MONGO_URI", "mongodb://localhost:27017"),
        tz_aware=True,
        serverSelectionTimeoutMS=3000,
    )
    db = client[os.getenv("MONGO_DB_NAME", "reviewdb")]
    
    reviews = db["reviews"]
    reviews.create_index([("user_id", ASCENDING), ("product_id", ASCENDING)], unique=True)
    reviews.create_index("product_id")

def get_collection():
    if db is None:
        raise RuntimeError("Database belum diinisialisasi")
    return db["reviews"]