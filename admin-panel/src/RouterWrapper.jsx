// IMPORTANT: Always call hooks (useState, useEffect, useContext, etc) at the top level of your component.
// Never call hooks inside conditions, loops, or after returns. This prevents hook order bugs.

import { useRoutes, Navigate } from 'react-router-dom';
import { useStateContext } from './contexts/AuthProvider.jsx';
import { useEffect, useState } from 'react';
import React from 'react';

import DefaultLayout from './layout/DefaultLayout.jsx';
import GuestLayout from './layout/GuestLayout.jsx';
import Login from './pages/user-auth/Login.jsx';
import Register from './pages/user-auth/Register.jsx';
import ForgotPassword from './pages/user-auth/ForgotPassword.jsx';
import Index from './pages/Index.jsx';

const generateUserRoutes = (routes) => {
  if (!Array.isArray(routes)) return [];

  return routes.map((route, index) => {
    const children = route.children ? generateUserRoutes(route.children) : [];

    return {
      path: route.path,
      element: <Index />, // You can map this to real components
      children,
    };
  });
};

const RedirectTo = () => {
  return <Navigate to="/dashboard" />;
};

export default function RouterWrapper() {
  const { token, userRoutes, loading } = useStateContext();
  const dynamicRoutes = generateUserRoutes(userRoutes || []);

  if (loading) {
    return null; // or a spinner if you want
  }

  const routes = [
    {
      path: '/',
      element: token ? <DefaultLayout /> : <Navigate to="/login" />,
      children: [
        { path: '/', element: <RedirectTo /> },
        { path: '/dashboard', element: <Index /> },
        ...dynamicRoutes,
        { path: '/profile', element: <Index /> },
      ],
    },
    {
      path: '/',
      element: <GuestLayout />,
      children: [
        { path: '/login', element: <Login /> },
        { path: '/sign-up', element: <Register /> },
        { path: '/forgot-password', element: <ForgotPassword /> },
      ],
    },
  ];

  return useRoutes(routes);
}