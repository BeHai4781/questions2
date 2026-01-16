import express from 'express';
import {
  register,
  login,
  getMe,
  refreshToken,
} from '../controllers/authController.js';
import { authenticate } from '../middleware/auth.js';
import { body } from 'express-validator';
import { validate } from '../middleware/validator.js';

const router = express.Router();

const registerValidation = [
  body('username')
    .trim()
    .isLength({ min: 3, max: 50 })
    .withMessage('Username must be between 3 and 50 characters'),
  body('email').isEmail().withMessage('Please provide a valid email'),
  body('password')
    .isLength({ min: 6 })
    .withMessage('Password must be at least 6 characters'),
  body('fullname')
    .trim()
    .notEmpty()
    .withMessage('Full name is required'),
];

const loginValidation = [
  body('username').notEmpty().withMessage('Username or email is required'),
  body('password').notEmpty().withMessage('Password is required'),
];

router.post('/register', registerValidation, validate, register);
router.post('/login', loginValidation, validate, login);
router.get('/me', authenticate, getMe);
router.post('/refresh', refreshToken);

export default router;

