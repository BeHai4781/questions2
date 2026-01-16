export default {
  secret: process.env.JWT_SECRET || 'default-secret-change-in-production',
  expiresIn: process.env.JWT_EXPIRE || '7d',
  refreshExpiresIn: process.env.JWT_REFRESH_EXPIRE || '30d',
};

