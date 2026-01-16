export default {
  maxSize: parseInt(process.env.UPLOAD_MAX_SIZE) || 5242880, 
  uploadPath: process.env.UPLOAD_PATH || './uploads',
  allowedImageTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
  allowedFileTypes: [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  ],
};

