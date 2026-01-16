export const successResponse = (res, data, message = 'Success', statusCode = 200) => {
  return res.status(statusCode).json({
    success: true,
    data,
    message,
  });
};

export const errorResponse = (res, message = 'Error', statusCode = 400, code = 'ERROR') => {
  return res.status(statusCode).json({
    success: false,
    error: {
      code,
      message,
    },
  });
};

export const paginatedResponse = (res, data, pagination, message = 'Success', statusCode = 200) => {
  return res.status(statusCode).json({
    success: true,
    data,
    pagination,
    message,
  });
};

