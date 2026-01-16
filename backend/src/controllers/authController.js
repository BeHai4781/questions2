import User from '../models/User.js';
import { generateToken, generateRefreshToken, verifyToken } from '../utils/jwt.js';
import { successResponse, errorResponse } from '../utils/response.js';

// @desc    Register new user
// @route   POST /api/auth/register
// @access  Public
export const register = async (req, res, next) => {
  try {
    const { username, email, password, fullname, phone, role } = req.body;

    const existingUser = await User.findOne({
      $or: [{ email }, { username }],
    });

    if (existingUser) {
      return errorResponse(
        res,
        'User with this email or username already exists',
        400,
        'USER_EXISTS'
      );
    }

    const user = await User.create({
      username,
      email,
      password,
      fullname,
      phone,
      role: role || 'student',
    });

    const token = generateToken(user._id);
    const refreshToken = generateRefreshToken(user._id);

    successResponse(
      res,
      {
        user,
        token,
        refreshToken,
      },
      'User registered successfully',
      201
    );
  } catch (error) {
    next(error);
  }
};


export const login = async (req, res, next) => {
  try {
    const { username, password } = req.body;

    const user = await User.findOne({
      $or: [{ username }, { email: username }],
    }).select('+password');

    if (!user) {
      return errorResponse(res, 'Invalid credentials', 401, 'INVALID_CREDENTIALS');
    }

    const isMatch = await user.comparePassword(password);
    if (!isMatch) {
      return errorResponse(res, 'Invalid credentials', 401, 'INVALID_CREDENTIALS');
    }

    if (user.status === 'banned') {
      return errorResponse(res, 'Account has been banned', 403, 'USER_BANNED');
    }

    const token = generateToken(user._id);
    const refreshToken = generateRefreshToken(user._id);

    successResponse(
      res,
      {
        user,
        token,
        refreshToken,
      },
      'Login successful'
    );
  } catch (error) {
    next(error);
  }
};

// @desc    Get current user
// @route   GET /api/auth/me
// @access  Private
export const getMe = async (req, res, next) => {
  try {
    const user = await User.findById(req.user._id);
    successResponse(res, { user }, 'User retrieved successfully');
  } catch (error) {
    next(error);
  }
};

// @desc    Refresh token
// @route   POST /api/auth/refresh
// @access  Public
export const refreshToken = async (req, res, next) => {
  try {
    const { refreshToken } = req.body;

    if (!refreshToken) {
      return errorResponse(res, 'Refresh token is required', 400, 'REFRESH_TOKEN_REQUIRED');
    }

    const decoded = verifyToken(refreshToken);
    const user = await User.findById(decoded.userId);

    if (!user) {
      return errorResponse(res, 'User not found', 404, 'USER_NOT_FOUND');
    }

    const newToken = generateToken(user._id);
    const newRefreshToken = generateRefreshToken(user._id);

    successResponse(
      res,
      {
        token: newToken,
        refreshToken: newRefreshToken,
      },
      'Token refreshed successfully'
    );
  } catch (error) {
    if (error.name === 'JsonWebTokenError' || error.name === 'TokenExpiredError') {
      return errorResponse(res, 'Invalid or expired refresh token', 401, 'INVALID_REFRESH_TOKEN');
    }
    next(error);
  }
};

