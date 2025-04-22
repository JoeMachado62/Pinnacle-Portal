class SampleController {
    getSample(req, res) {
        // Logic to retrieve sample data
        res.send("Sample data retrieved successfully.");
    }

    createSample(req, res) {
        // Logic to create new sample data
        res.send("Sample data created successfully.");
    }
}

module.exports = SampleController;