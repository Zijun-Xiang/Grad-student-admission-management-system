import React from 'react';
import Layout from '../components/layout/Layout';
import './Milestones.css';

const Milestones = () => {
  return (
    <Layout>
      <div className="page-shell milestones-page">
        <div className="page-grid wide">
          <div className="card">
            <div className="card-header">
              <span className="card-title">Milestones</span>
              <span className="pill">Milestone Tracking</span>
            </div>
            <div className="card-body">
              <div className="muted">This page is a placeholder for milestone tracking.</div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default Milestones;
