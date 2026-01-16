import express from 'express';
import authRoutes from './authRoutes.js';

const router = express.Router();

router.get('/health', (req, res) => {
  res.json({
    success: true,
    message: 'Server is running',
    timestamp: new Date().toISOString(),
  });
});

router.use('/auth', authRoutes);

// TODO: Thêm các routes khác
// router.use('/users', userRoutes);
// router.use('/exams', examRoutes);
// router.use('/questions', questionRoutes);
// router.use('/attempts', attemptRoutes);
// router.use('/notifications', notificationRoutes);
// router.use('/upload', uploadRoutes);

export default router;

