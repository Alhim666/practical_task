import { useState } from 'react';

function CarInfo() {
  const [carId, setCarId] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [response, setResponse] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

    setResponse(null);
    setError(null);
    setLoading(true);

    try {
      const res = await fetch('http://carrent/wp-json/car_checker/v1/free-days', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          car_id: carId,
          start_date: startDate,
          end_date: endDate,
        }),
      });

      if (!res.ok) {
        throw new Error('Error fetching car data');
      }

      const data = await res.json();

      if (data.status === 'success') {
        setResponse(data.data);
      } else {
        setError(data.message);
      }
    } catch (error) {
      setError('Failed to fetch data');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h1>Car Info</h1>
      
      <form onSubmit={handleSubmit}>
        <div>
          <label htmlFor="carId">Car ID:</label>
          <input
            type="number"
            id="carId"
            value={carId}
            onChange={(e) => setCarId(e.target.value)}
            required
          />
        </div>

        <div>
          <label htmlFor="startDate">Start Date:</label>
          <input
            type="date"
            id="startDate"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
            required
          />
        </div>

        <div>
          <label htmlFor="endDate">End Date:</label>
          <input
            type="date"
            id="endDate"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            required
          />
        </div>

        <button type="submit" disabled={loading}>
          {loading ? 'Loading...' : 'Get Car Info'}
        </button>
      </form>

      {response && (
        <div>
          <h2>Car Info</h2>
          <p>Brand: {response.car_info.brand_slug}</p>
          <p>Model: {response.car_info.model_slug}</p>
          <p>Free Days: {response.free_days}</p>
          <p>Total Days: {response.all_days}</p>
        </div>
      )}

      {error && <div style={{ color: 'red' }}>{error}</div>}
    </div>
  );
}

export default CarInfo;
