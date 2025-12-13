import React, { useContext, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import './Navbar.css';
import { UserContext } from '../../context/UserContext';
import Sidebar from './Sidebar';

const Navbar = () => {
  const { user, logout } = useContext(UserContext);
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/signin');
    } catch (error) {
      console.error('Logout failed:', error);
      navigate('/signin');
    }
  };

  const handleLogin = () => {
    navigate('/signin');
  };

  const displayName = user ? `${user.first_name} ${user.last_name}` : 'Guest';

  return (
    <header className="navbar">
      <div className="navbar-left">
        <button
          className="menu-toggle"
          onClick={() => setMenuOpen((v) => !v)}
          aria-label="Toggle navigation menu"
        >
          ☰
        </button>
        <div
          className="brand"
          onClick={() => navigate(user ? '/dashboard' : '/signin')}
          onKeyDown={(e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              navigate(user ? '/dashboard' : '/signin');
            }
          }}
          role="button"
          tabIndex={0}
        >
          <div className="brand-mark">GT</div>
          <div className="brand-text">
            <div className="brand-title">GradTrack</div>
            <div className="brand-subtitle">Graduate Tracking</div>
          </div>
        </div>
      </div>

      <div className="navbar-center">
        <Sidebar isOpen={menuOpen} toggleSidebar={() => setMenuOpen(false)} />
      </div>

      <div className="navbar-right">
        <div className="navbar-welcome">Welcome, {displayName}</div>
        <div className="navbar-actions">
          {user ? (
            <>
              <a href="/settings" className="nav-btn">Settings</a>
              <button className="nav-btn logout" onClick={handleLogout}>Logout</button>
            </>
          ) : (
            <button className="nav-btn login" onClick={handleLogin}>Login</button>
          )}
        </div>
      </div>
    </header>
  );
};


export default Navbar;
