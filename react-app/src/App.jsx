import { BrowserRouter as Router, Route, Routes, Link } from 'react-router-dom';
import AllCarsInfo from './components/AllCarsInfo';
import CarInfo from './components/CarInfo';

function App() {
  return (
    <Router>
      <div>
        <nav>
          <ul>
            <li><Link to="/">Home</Link></li>
            <li><Link to="/car/">Car</Link></li>
          </ul>
        </nav>

        <Routes>
          <Route path="/" element={<AllCarsInfo />} />
          <Route path="/car/" element={<CarInfo />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
