class SampleModel {
    constructor(data) {
        this.data = data;
    }

    getData() {
        return this.data;
    }

    setData(newData) {
        this.data = newData;
    }

    validate() {
        // Add validation logic here
        return true;
    }
}

export default SampleModel;