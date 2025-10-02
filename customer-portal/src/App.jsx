import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useStateContext, ContextProvider } from './contexts/AuthProvider';
import Login from './pages/auth/Login';
import ForgotPassword from './pages/auth/ForgotPassword';
import Dashboard from './pages/Dashboard';
import Invoices from './pages/Invoices';
import InvoiceDetail from './pages/InvoiceDetail';
import PaymentSubmission from './pages/PaymentSubmission';
import PaymentHistory from './pages/PaymentHistory';
import Profile from './pages/Profile';
import Layout from './components/Layout';
import LoadingSpinner from './components/LoadingSpinner';

function App() {
  const { token, loading } = useStateContext();

  if (loading) {
    return <LoadingSpinner />;
  }

  return (
    <Router>
      <div className="App">
        <Routes>
          {/* Public Routes */}
          <Route path="/login" element={!token ? <Login /> : <Navigate to="/dashboard" />} />
          <Route path="/forgot-password" element={!token ? <ForgotPassword /> : <Navigate to="/dashboard" />} />
          
          {/* Protected Routes */}
          <Route path="/" element={token ? <Layout /> : <Navigate to="/login" />}>
            <Route index element={<Navigate to="/dashboard" />} />
            <Route path="dashboard" element={<Dashboard />} />
            <Route path="invoices" element={<Invoices />} />
            <Route path="invoices/:id" element={<InvoiceDetail />} />
            <Route path="payment-submission" element={<PaymentSubmission />} />
            <Route path="payment-history" element={<PaymentHistory />} />
            <Route path="profile" element={<Profile />} />
          </Route>
          
          {/* Catch all route */}
          <Route path="*" element={<Navigate to="/dashboard" />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
