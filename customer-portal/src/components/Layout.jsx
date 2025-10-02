import { Outlet } from 'react-router-dom';
import { useStateContext } from '../contexts/AuthProvider';
import Sidebar from './Sidebar';
import Header from './Header';

const Layout = () => {
  const { user } = useStateContext();

  return (
    <div className="d-flex">
      <Sidebar />
      <div className="flex-grow-1 d-flex flex-column">
        <Header user={user} />
        <main className="flex-grow-1 p-4">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default Layout;
