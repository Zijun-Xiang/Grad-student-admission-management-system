import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../components/layout/Layout';
import MilestoneCard from '../components/widgets/MilestoneCard';
import MajorCompletionWidget from '../components/widgets/MajorCompletionWidget';
import DeadlineList from '../components/widgets/DeadlineList';
import DocumentVault from '../components/widgets/DocumentVaultWidget';
import AdvisorContactWidget from '../components/widgets/AdvisorContactWidget';
import CalendarWidget from '../components/widgets/CalendarWidget';
// import EvaluationStatus from '../components/widgets/EvaluationStatus';
// import ReminderPanel from '../components/widgets/ReminderPanel';
// import QuickActions from '../components/widgets/QuickActions';
import './Dashboard.css';
import { useContext } from 'react';
import { UserContext } from '../context/UserContext';

const Dashboard = () => {
  const [deadlines, setDeadlines] = useState([]);
  const [searchInput, setSearchInput] = useState('');
  const [searchSuggestions, setSearchSuggestions] = useState([]);
  const { user, loading } = useContext(UserContext);
  const navigate = useNavigate();

  // Define available pages and shortcuts
  const pageMap = {
      'planner': '/course-planner',
      'course planner': '/course-planner',
      'schedule': '/course-planner',
      'courses': '/course-planner',
      'documents': '/documents',
      'vault': '/documents',
      'document vault': '/documents',
      'settings': '/settings',
      'dashboard': '/dashboard',
      'home': '/dashboard',
  };

  const handleSearchChange = (e) => {
    const value = e.target.value.toLowerCase();
    setSearchInput(value);

    // Filter suggestions based on input
    if (value.trim()) {
      const matches = Object.keys(pageMap).filter(key =>
        key.includes(value)
      );
      setSearchSuggestions(matches.slice(0, 5)); // Show up to 5 suggestions
    } else {
      setSearchSuggestions([]);
    }
  };

  const navigateToPage = (query) => {
    const normalizedQuery = query.toLowerCase().trim();
    const path = pageMap[normalizedQuery];

    if (path) {
      navigate(path);
      setSearchInput('');
      setSearchSuggestions([]);
    } else {
      alert(`Page not found. Try: ${Object.keys(pageMap).join(', ')}`);
    }
  };

  const handleSearchSubmit = (e) => {
    if (e.key === 'Enter') {
      navigateToPage(searchInput);
    }
  };

  const handleSuggestionClick = (suggestion) => {
    navigateToPage(suggestion);
  };

  useEffect(() => {
    // TODO: Replace with Laravel API call to /api/deadlines
    setDeadlines([
      { id: 1, label: 'Submit Program of Study', date: '2025-10-20' },
      { id: 2, label: 'Annual Evaluation Due', date: '2025-11-05' },
    ]);


  }, []);



  return (
    <Layout>
      <div className="page-shell dashboard-page">
        <div className="card search-card">
          <div className="card-body">
            <div className="search-head">
              <div>
                <div className="section-heading">Find a feature</div>
                <div className="muted">Jump straight to any tool or workflow</div>
              </div>
              <span className="pill">Quick Nav</span>
            </div>
            <div className="searchbar-container">
              <input
                type="text"
                className="searchbar"
                placeholder="Type 'documents', 'course planner', or 'settings'"
                value={searchInput}
                onChange={handleSearchChange}
                onKeyDown={handleSearchSubmit}
              />
              {searchSuggestions.length > 0 && (
                <div className="search-suggestions">
                  {searchSuggestions.map(suggestion => (
                    <button
                      key={suggestion}
                      className="suggestion-btn"
                      onClick={() => handleSuggestionClick(suggestion)}
                    >
                      {suggestion}
                    </button>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="page-grid wide dashboard-grid">
          <div className="card">
            <div className="card-header">
              <span className="card-title">Document Vault</span>
              <span className="pill">Files</span>
            </div>
            <div className="card-body card-body-flush">
              <DocumentVault />
            </div>
          </div>

          <div className="card">
            <div className="card-header">
              <span className="card-title">Advisor Contact</span>
            </div>
            <div className="card-body">
              <AdvisorContactWidget facultyId={user?.id || 1} />
            </div>
          </div>

          <div className="card">
            <div className="card-header">
              <span className="card-title">Progress</span>
              <span className="pill">Program</span>
            </div>
            <div className="card-body">
              <MajorCompletionWidget studentId={user?.id || 1} />
            </div>
          </div>

          <div className="card">
            <div className="card-header">
              <span className="card-title">Milestones</span>
            </div>
            <div className="card-body">
              <MilestoneCard studentId={user?.id} />
            </div>
          </div>

          <div className="card">
            <div className="card-header">
              <span className="card-title">Deadlines</span>
            </div>
            <div className="card-body">
              <DeadlineList deadlines={deadlines} />
            </div>
          </div>

          <CalendarWidget className="grid-span-2 reminder-prominent" />
        </div>
      </div>
    </Layout>
  );
};

export default Dashboard;
