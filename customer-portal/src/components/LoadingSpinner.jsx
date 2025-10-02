import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { solidIconMap } from '../utils/solidIcons';

const LoadingSpinner = () => {
  return (
    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
      <div className="text-center">
        <FontAwesomeIcon 
          icon={solidIconMap.spinner} 
          className="fa-spin text-champagne" 
          style={{ fontSize: '3rem' }} 
        />
        <p className="mt-3 text-muted">Loading...</p>
      </div>
    </div>
  );
};

export default LoadingSpinner;
