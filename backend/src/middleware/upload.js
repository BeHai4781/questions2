import multer from 'multer';
import path from 'path';
import { fileURLToPath } from 'url';
import uploadConfig from '../config/upload.js';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const uploadDir = path.join(__dirname, '../../', uploadConfig.uploadPath);
if (!fs.existsSync(uploadDir)) {
  fs.mkdirSync(uploadDir, { recursive: true });
}

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadDir);
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname);
    const name = path.basename(file.originalname, ext);
    cb(null, `${name}-${uniqueSuffix}${ext}`);
  },
});

const fileFilter = (req, file, cb) => {
  const allowedTypes = [
    ...uploadConfig.allowedImageTypes,
    ...uploadConfig.allowedFileTypes,
  ];

  if (allowedTypes.includes(file.mimetype)) {
    cb(null, true);
  } else {
    cb(
      new Error(
        `Invalid file type. Allowed types: ${allowedTypes.join(', ')}`
      ),
      false
    );
  }
};

const upload = multer({
  storage: storage,
  limits: {
    fileSize: uploadConfig.maxSize,
  },
  fileFilter: fileFilter,
});

export const uploadImage = upload.single('image');

export const uploadFile = upload.single('file');

export const uploadMultiple = upload.array('files', 10);

