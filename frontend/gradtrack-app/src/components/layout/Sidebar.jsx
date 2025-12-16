import React, { useContext } from 'react';
import { Link } from 'react-router-dom';
import { UserContext } from '../../context/UserContext';
import './Sidebar.css';

const Sidebar = ({ isOpen, toggleSidebar }) => {
  const { user } = useContext(UserContext);

  // Determine dashboard route based on user role
  const getDashboardRoute = () => {
    if (!user || !user.role) return '/dashboard'; // Default to student dashboard
    
    switch (user.role) {
      case 'student':
        return '/dashboard';
      case 'faculty':
        return '/faculty-dashboard';
      case 'admin':
        return '/admin-dashboard';
      default:
        return '/dashboard';
    }
  };

  const handleNavigate = () => {
    if (isOpen && typeof toggleSidebar === 'function') {
      toggleSidebar();
    }
  };

  return (
    <>
      <nav className={`header-nav ${isOpen ? 'open' : ''}`} aria-label="Primary navigation">
        <ul className="nav-links">
          <li><Link to={getDashboardRoute()} onClick={handleNavigate}>Dashboard</Link></li>
          <li><Link to="/reminders" onClick={handleNavigate}>Reminders</Link></li>
          {user?.role === 'student' && (
            <>
              <li><Link to="/documents" onClick={handleNavigate}>Documents</Link></li>
              <li><Link to="/course-planner" onClick={handleNavigate}>Course Planner</Link></li>
            </>
          )}
          {(user?.role === 'admin' || user?.role === 'faculty') && (
            <>
              <li><Link to="/admin/documents" onClick={handleNavigate}>Document Review</Link></li>
              <li><Link to="/courses" onClick={handleNavigate}>Course Adder</Link></li>
            </>
          )}
        </ul>
      </nav>
    </>
  );
};

export default Sidebar;
