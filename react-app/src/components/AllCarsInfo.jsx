import { useState, useEffect } from 'react';

function AllCarsInfo() {
  const [cars, setCars] = useState([]);
  const [loading, setLoading] = useState(false); 
  const [error, setError] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [startDate, setStartDate] = useState('2024-01-01');
  const [endDate, setEndDate] = useState('2024-12-31');
  const [perPage] = useState(10);

  const fetchCars = async (startDate, endDate, page = 1, perPage = 10) => {
    setLoading(true); 
    try {
      const response = await fetch('http://carrent/wp-json/car_checker/v1/free-days-all', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          start_date: startDate,
          end_date: endDate,
          page,
          per_page: perPage,
        }),
      });

      if (!response.ok) {
        throw new Error('Error fetching car data');
      }

      const data = await response.json();

      if (data.status === 'success') {
        setCars(data.data);
        setTotalPages(data.pagination.total_pages);
        setLoading(false); 
      } else {
        setError(data.message);
        setLoading(false);
      }
    } catch (error) {
      setError('Failed to fetch data');
      setLoading(false);
    }
  };

  const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages) {
      setCurrentPage(page);
      fetchCars(startDate, endDate, page, perPage);
    }
  };

  const handleDateChange = (e) => {
    e.preventDefault();
    fetchCars(startDate, endDate, 1, perPage);
  };

  useEffect(() => {
    fetchCars(startDate, endDate, currentPage, perPage);
  }, [currentPage, startDate, endDate, perPage]);

  return (
    <div>
      <h2>All Cars Info</h2>

      <form onSubmit={handleDateChange}>
        <div>
          <label htmlFor="startDate">Start Date:</label>
          <input
            type="date"
            id="startDate"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
          />
        </div>

        <div>
          <label htmlFor="endDate">End Date:</label>
          <input
            type="date"
            id="endDate"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
          />
        </div>

        <button type="submit">Apply Date Range</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>Car Brand</th>
            <th>Model</th>
            <th>Free Days</th>
            <th>Total Days</th>
          </tr>
        </thead>
        <tbody>
          {cars.map((car, index) => (
            <tr key={index}>
              <td>{car.car_info.brand_slug}</td>
              <td>{car.car_info.model_slug}</td>
              <td>{car.free_days}</td>
              <td>{car.total_days}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <div>
        <button
          onClick={() => handlePageChange(currentPage - 1)}
          disabled={currentPage <= 1}
        >
          Previous
        </button>

        <span>
          Page {currentPage} of {totalPages}
        </span>

        <button
          onClick={() => handlePageChange(currentPage + 1)}
          disabled={currentPage >= totalPages}
        >
          Next
        </button>

        <div>
          <label htmlFor="pageInput">Go to Page:</label>
          <input
            type="number"
            id="pageInput"
            min="1"
            max={totalPages}
            value={currentPage}
            onChange={(e) => handlePageChange(Number(e.target.value))}
          />
        </div>
      </div>

      {loading && (
        <div className="loading-spinner">
          <p>Loading...</p>
        </div>
      )}

      {error && <div className="error">{error}</div>}
    </div>
  );
}

export default AllCarsInfo;
