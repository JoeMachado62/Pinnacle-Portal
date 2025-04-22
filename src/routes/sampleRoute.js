const express = require('express');
const SampleController = require('../controllers/sampleController');

const router = express.Router();
const sampleController = new SampleController();

function setRoutes(app) {
    router.get('/samples', sampleController.getSample.bind(sampleController));
    router.post('/samples', sampleController.createSample.bind(sampleController));
    
    app.use('/api', router);
}

module.exports = setRoutes;