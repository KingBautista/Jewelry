import { createContext, useContext, useState, useEffect } from 'react';
import axiosClient from '../axios-client';

const StateContext = createContext({});

export const useStateContext = () => useContext(StateContext);

export const ContextProvider = ({ children }) => {
  const [token, setToken] = useState(localStorage.getItem('CUSTOMER_TOKEN'));
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (token) {
      axiosClient.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      axiosClient.get('/customer/user')
        .then(({ data }) => {
          setUser(data);
        })
        .catch(() => {
          setToken(null);
          setUser(null);
          localStorage.removeItem('CUSTOMER_TOKEN');
        })
        .finally(() => {
          setLoading(false);
        });
    } else {
      setLoading(false);
    }
  }, [token]);

  const login = (email, password) => {
    return axiosClient.post('/customer/login', { email, password })
      .then(({ data }) => {
        setToken(data.token);
        setUser(data.user);
        localStorage.setItem('CUSTOMER_TOKEN', data.token);
        axiosClient.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
        
        // Return a promise that resolves after state updates
        return new Promise((resolve) => {
          // Use setTimeout to ensure state updates are processed
          setTimeout(() => {
            resolve(data);
          }, 0);
        });
      });
  };

  const logout = () => {
    setToken(null);
    setUser(null);
    localStorage.removeItem('CUSTOMER_TOKEN');
    delete axiosClient.defaults.headers.common['Authorization'];
  };

  const forgotPassword = (email) => {
    return axiosClient.post('/customer/forgot-password', { email });
  };

  return (
    <StateContext.Provider value={{
      token,
      user,
      setToken,
      setUser,
      login,
      logout,
      forgotPassword,
      loading
    }}>
      {children}
    </StateContext.Provider>
  );
};
