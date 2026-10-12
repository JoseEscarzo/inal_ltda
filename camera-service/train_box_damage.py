"""Entrena YOLOv8 para detectar daño en cajas y guarda las métricas de validación."""

from __future__ import annotations

import argparse
import json
from pathlib import Path

import yaml
from ultralytics import YOLO


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dataset", required=True, help="Ruta a box_damage.v4i.yolov8")
    parser.add_argument("--epochs", type=int, default=60)
    parser.add_argument("--imgsz", type=int, default=640)
    args = parser.parse_args()

    dataset = Path(args.dataset).resolve()
    config = {
        "train": str(dataset / "train" / "images"),
        "val": str(dataset / "valid" / "images"),
        "names": ["damage", "open"],
    }
    config_path = Path(__file__).resolve().parent / "dataset_box_damage.yaml"
    config_path.write_text(yaml.safe_dump(config, sort_keys=False), encoding="utf-8")

    service_dir = Path(__file__).resolve().parent
    model = YOLO("yolov8n.pt")
    results = model.train(
        data=str(config_path),
        epochs=args.epochs,
        imgsz=args.imgsz,
        project=str(service_dir / "runs"),
        name="box_damage",
        exist_ok=True,
        device="cpu",
        workers=0,
        patience=20,
        plots=True,
    )

    run_dir = Path(results.save_dir)
    best_model = run_dir / "weights" / "best.pt"
    target_dir = service_dir / "models"
    target_dir.mkdir(exist_ok=True)
    target = target_dir / "box_damage_best.pt"
    target.write_bytes(best_model.read_bytes())

    validation = YOLO(str(target)).val(data=str(config_path), imgsz=args.imgsz, device="cpu", workers=0, plots=True)
    metrics = {
        "dataset": str(dataset),
        "model": str(target),
        "epochs": args.epochs,
        "precision": round(float(validation.results_dict.get("metrics/precision(B)", 0)), 4),
        "recall": round(float(validation.results_dict.get("metrics/recall(B)", 0)), 4),
        "map50": round(float(validation.results_dict.get("metrics/mAP50(B)", 0)), 4),
        "map50_95": round(float(validation.results_dict.get("metrics/mAP50-95(B)", 0)), 4),
    }
    (target_dir / "box_damage_metrics.json").write_text(json.dumps(metrics, indent=2), encoding="utf-8")
    print(json.dumps(metrics, indent=2))


if __name__ == "__main__":
    main()
