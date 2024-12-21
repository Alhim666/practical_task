document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('car-availability-form');
    const resultsContainer = document.getElementById('car-results');

    let currentPage = 1;
    let totalPages = 1;
    const perPage = 10;

    async function fetchPageData(page = 1) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }

        try {
            const response = await fetch('/wp-json/car_checker/v1/free-days-all', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate,
                    page: page,
                    per_page: perPage,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to fetch data');
            }

            const data = await response.json();

            if (data.status === 'error') {
                throw new Error(data.message);
            }

            totalPages = data.pagination.total_pages;
            renderTable(data.data);
            renderPagination();
        } catch (error) {
            resultsContainer.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
        }
    }

    function renderTable(data) {
        if (data.length === 0) {
            resultsContainer.innerHTML = '<p>No results found.</p>';
            return;
        }

        let table = '<table border="1"><thead><tr>';
        table += '<th>Car ID</th><th>Brand</th><th>Model</th><th>Free Days</th><th>Total Days</th></tr></thead><tbody>';

        data.forEach((car) => {
            const baseUrl = window.location.origin;
            const carUrl = `${baseUrl}/car-info/?id=${car.car_id}`;

            table += `
        <tr>
            <td><a href="${carUrl}" target="_blank">${car.car_id}</a></td>
            <td>${car.car_info?.brand_slug || 'N/A'}</td>
            <td>${car.car_info?.model_slug || 'N/A'}</td>
            <td>${car.free_days || '0'}</td>
            <td>${car.total_days || '0'}</td>
        </tr>
    `;
        });

        table += '</tbody></table>';
        resultsContainer.innerHTML = table;
    }

    function renderPagination() {
        const pagination = document.createElement('div');
        pagination.innerHTML = `
            <button ${currentPage === 1 ? 'disabled' : ''} id="prev_page">Previous</button>
            <input type="number" id="page_input" value="${currentPage}" min="1" max="${totalPages}" />
            <button ${currentPage === totalPages ? 'disabled' : ''} id="next_page">Next</button>
        `;

        resultsContainer.appendChild(pagination);

        document.getElementById('prev_page').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchPageData(currentPage);
            }
        });

        document.getElementById('next_page').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                fetchPageData(currentPage);
            }
        });

        document.getElementById('page_input').addEventListener('change', (e) => {
            const page = parseInt(e.target.value, 10);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                fetchPageData(currentPage);
            }
        });
    }

    document.getElementById('get_data').addEventListener('click', () => {
        currentPage = 1;
        fetchPageData();
    });
});
