import os
import time
import base64
from pathlib import Path

import cv2
import numpy as np
from dotenv import load_dotenv
from flask import Flask, Response, jsonify, request
from flask_cors import CORS

load_dotenv()

BASE_DIR = Path(__file__).resolve().parent
VIDEOS_DIR = BASE_DIR / "videos"
MODELS_DIR = BASE_DIR / "models"
FPS = max(1, int(os.getenv("CAMERA_FPS", "24")))
DETECTION_CONFIDENCE = float(os.getenv("DETECTION_CONFIDENCE", "0.20"))
MODEL_PATH = Path(os.getenv("YOLO_MODEL_PATH", str(MODELS_DIR / "box_damage_best.pt")))

app = Flask(__name__)
CORS(app)
model = None
model_load_error = None


def load_detection_model():
    """Carga el modelo una sola vez, únicamente cuando se solicita una detección."""
    global model, model_load_error
    if model is not None:
        return model
    if model_load_error:
        raise RuntimeError(model_load_error)
    if not MODEL_PATH.exists():
        model_load_error = f"Modelo YOLO no encontrado: {MODEL_PATH}"
        raise RuntimeError(model_load_error)
    try:
        from ultralytics import YOLO
        model = YOLO(str(MODEL_PATH))
        return model
    except Exception as error:
        model_load_error = str(error)
        raise RuntimeError(model_load_error) from error


def discover_cameras():
    videos = sorted(
        [p for p in VIDEOS_DIR.glob("*") if p.suffix.lower() in {".mp4", ".avi", ".mov", ".mkv"}]
    )

    cameras = {}
    labels = [
        "Linea 1 - Camara 1",
        "Linea 1 - Camara 2",
        "Linea 2 - Camara 1",
        "Linea 2 - Camara 2",
        "Linea 3 - Camara 1",
        "Linea 3 - Camara 2",
    ]

    for index, video in enumerate(videos):
        cam_id = f"cam_{index + 1}"
        cameras[cam_id] = {
            "id": cam_id,
            "name": labels[index] if index < len(labels) else f"Camara {index + 1}",
            "source": str(video),
            "type": "video",
            "status": "Activa",
        }

    if not cameras:
        for index in range(1, 5):
            cam_id = f"sim_{index}"
            cameras[cam_id] = {
                "id": cam_id,
                "name": f"Camara simulada {index}",
                "source": None,
                "type": "simulated",
                "status": "Activa",
            }

    return cameras


CAMERAS = discover_cameras()


def encode_frame(frame):
    ok, buffer = cv2.imencode(".jpg", frame, [cv2.IMWRITE_JPEG_QUALITY, 82])
    if not ok:
        return None
    return buffer.tobytes()


def simulated_frame(camera_name, frame_count):
    frame = np.zeros((480, 854, 3), dtype=np.uint8)
    frame[:] = (30, 38, 52)
    pulse = 80 + int(60 * abs(np.sin(frame_count / 18)))
    cv2.rectangle(frame, (34, 34), (820, 446), (70, 86, 112), 2)
    cv2.putText(frame, camera_name, (64, 128), cv2.FONT_HERSHEY_SIMPLEX, 1.1, (255, 255, 255), 2)
    cv2.putText(frame, "STREAM SIMULADO", (64, 196), cv2.FONT_HERSHEY_SIMPLEX, 0.9, (80, 220, 150), 2)
    cv2.circle(frame, (760, 92), 18, (0, pulse, 0), -1)
    cv2.putText(frame, time.strftime("%Y-%m-%d %H:%M:%S"), (64, 390), cv2.FONT_HERSHEY_SIMPLEX, 0.75, (210, 220, 235), 2)
    return frame


def generate_video_frames(camera):
    source = camera.get("source")
    frame_delay = 1 / FPS

    if not source:
        count = 0
        while True:
            frame = simulated_frame(camera["name"], count)
            jpg = encode_frame(frame)
            if jpg:
                yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + jpg + b"\r\n"
            count += 1
            time.sleep(frame_delay)

    while True:
        capture = cv2.VideoCapture(source)
        if not capture.isOpened():
            camera["status"] = "Sin conexion"
            count = 0
            while count < FPS * 3:
                frame = simulated_frame(f"{camera['name']} - sin conexion", count)
                jpg = encode_frame(frame)
                if jpg:
                    yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + jpg + b"\r\n"
                count += 1
                time.sleep(frame_delay)
            continue

        camera["status"] = "Activa"
        while True:
            ok, frame = capture.read()
            if not ok:
                break

            frame = cv2.resize(frame, (854, 480))
            cv2.rectangle(frame, (0, 0), (854, 42), (0, 0, 0), -1)
            cv2.putText(frame, camera["name"], (16, 28), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
            cv2.circle(frame, (824, 21), 8, (0, 220, 120), -1)
            jpg = encode_frame(frame)
            if jpg:
                yield b"--frame\r\nContent-Type: image/jpeg\r\n\r\n" + jpg + b"\r\n"
            time.sleep(frame_delay)

        capture.release()


@app.get("/health")
def health():
    return jsonify({
        "ok": True,
        "cameras": len(CAMERAS),
        "fps": FPS,
        "videos": len([cam for cam in CAMERAS.values() if cam.get("type") == "video"]),
        "videos_dir": str(VIDEOS_DIR),
        "model_ready": MODEL_PATH.exists(),
        "model_path": str(MODEL_PATH),
    })


@app.post("/api/detect")
def detect_damage():
    """Recibe una imagen y devuelve la misma imagen marcada solo si hay daño."""
    image_file = request.files.get("image")
    if image_file is None or not image_file.filename:
        return jsonify({"message": "Debe seleccionar una imagen."}), 422

    allowed_extensions = {".jpg", ".jpeg", ".png", ".webp"}
    allowed_mime_types = {"image/jpeg", "image/png", "image/webp"}
    extension = Path(image_file.filename).suffix.lower()
    if extension not in allowed_extensions or image_file.mimetype not in allowed_mime_types:
        return jsonify({
            "message": "Solo se permiten imágenes JPG, PNG o WEBP. No se admiten PDF, Word ni otros documentos."
        }), 422

    binary = image_file.read()
    frame = cv2.imdecode(np.frombuffer(binary, dtype=np.uint8), cv2.IMREAD_COLOR)
    if frame is None:
        return jsonify({"message": "El archivo no es una imagen válida."}), 422

    try:
        detector = load_detection_model()
        result = detector.predict(frame, conf=DETECTION_CONFIDENCE, verbose=False)[0]
    except RuntimeError as error:
        return jsonify({"message": str(error)}), 503
    except Exception as error:
        return jsonify({"message": f"No se pudo ejecutar la detección: {error}"}), 500

    detections = []
    boxes = result.boxes
    if boxes is not None:
        for box in boxes:
            x1, y1, x2, y2 = [int(value) for value in box.xyxy[0].tolist()]
            confidence = float(box.conf[0])
            class_id = int(box.cls[0])
            label = result.names[class_id]
            detections.append({
                "class": label,
                "confidence": round(confidence, 4),
                "box": [x1, y1, x2, y2],
            })
            # Amarillo en BGR: coincide con el estado visual "No conforme" del formulario.
            cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 255), 3)
            caption = f"{label} {confidence:.0%}"
            cv2.putText(frame, caption, (x1, max(28, y1 - 9)), cv2.FONT_HERSHEY_SIMPLEX, 0.72, (0, 255, 255), 2)

    encoded = encode_frame(frame)
    return jsonify({
        "damaged": bool(detections),
        "detections": detections,
        "confidence_threshold": DETECTION_CONFIDENCE,
        "annotated_image": "data:image/jpeg;base64," + base64.b64encode(encoded).decode("ascii"),
    })


@app.get("/api/cameras")
def cameras():
    base = os.getenv("PUBLIC_CAMERA_BASE_URL", "http://localhost:5001")
    return jsonify([
        {
            "id": cam["id"],
            "name": cam["name"],
            "status": cam["status"],
            "type": cam["type"],
            "stream_url": f"{base}/stream/{cam['id']}",
        }
        for cam in CAMERAS.values()
    ])


@app.get("/stream/<camera_id>")
def stream(camera_id):
    camera = CAMERAS.get(camera_id)
    if not camera:
        return jsonify({"message": "Camara no encontrada"}), 404
    return Response(generate_video_frames(camera), mimetype="multipart/x-mixed-replace; boundary=frame")


if __name__ == "__main__":
    host = os.getenv("CAMERA_SERVICE_HOST", "0.0.0.0")
    port = int(os.getenv("CAMERA_SERVICE_PORT", "5001"))
    debug = os.getenv("CAMERA_SERVICE_DEBUG", "true").lower() == "true"
    print(f"Camera service running at http://localhost:{port}")
    print(f"Videos folder: {VIDEOS_DIR}")
    app.run(host=host, port=port, debug=debug, threaded=True)
