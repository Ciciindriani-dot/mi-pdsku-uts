from flask import Flask
from app.database import init_db
from app.routes.review_routes import review_bp

def create_app():
    app = Flask(__name__)
    
    init_db()
    
    app.register_blueprint(review_bp)
    
    @app.route("/health", methods=["GET"])
    def health_check():
        return {"status": "healthy"}, 200

    return app