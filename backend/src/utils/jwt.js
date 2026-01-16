import jwt from 'jsonwebtoken';
import jwtConfig from '../config/jwt.js';

export const generateToken = (userId) => {
  return jwt.sign({ userId }, jwtConfig.secret, {
    expiresIn: jwtConfig.expiresIn,
  });
};

export const generateRefreshToken = (userId) => {
  return jwt.sign({ userId }, jwtConfig.secret, {
    expiresIn: jwtConfig.refreshExpiresIn,
  });
};

export const verifyToken = (token) => {
  return jwt.verify(token, jwtConfig.secret);
};

