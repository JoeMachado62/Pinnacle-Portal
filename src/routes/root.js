module.exports = {
  routes: [
    {
      method: 'GET',
      path: '/',
      handler: 'root.index',
      config: {
        auth: false,
      },
    },
  ],
};
