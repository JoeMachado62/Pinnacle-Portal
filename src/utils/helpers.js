export const formatResponse = (data, message = 'Success', status = 200) => {
    return {
        status,
        message,
        data,
    };
};

export const handleError = (error, message = 'An error occurred', status = 500) => {
    console.error(error);
    return {
        status,
        message,
        error: error.message || message,
    };
};