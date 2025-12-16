import React from 'react';
import Navbar from './Navbar';
import Footer from './Footer'; 

const Layout = ({ children }) => {
  return (
    <div className="app-layout">
      <Navbar />
      <div
        className="main-content"
        style={{
          marginTop: '72px',
          flex: 1,
        }}
      >
        <div className="content-inner">
          {children}
        </div>
        <Footer />
      </div>
    </div>
  );
}

export default Layout;
