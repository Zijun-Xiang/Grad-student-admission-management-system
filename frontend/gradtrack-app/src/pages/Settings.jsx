import React, { useState, useContext, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../components/layout/Layout';
import { UserContext } from '../context/UserContext';
import API_CONFIG from '../api/config';
import './Settings.css';

const Settings = () => {
  const { user, setUser, loading: userLoading } = useContext(UserContext);
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    department: '',
    password: '',
    password_confirmation: '',
  });

  // Load user data when component mounts
  useEffect(() => {
    // Wait for user context to finish loading before checking
    if (userLoading) {
      return;
    }

    // Only redirect if loading is complete and user is still null
    if (!user) {
      navigate('/signin');
      return;
    }

    setFormData({
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      email: user.email || '',
      department: user.department || '',
      password: '',
      password_confirmation: '',
    });
  }, [user, userLoading, navigate]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear messages when user starts typing
    if (successMessage) setSuccessMessage('');
    if (errorMessage) setErrorMessage('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setSuccessMessage('');
    setErrorMessage('');

    // Validate password if provided
    if (formData.password && formData.password.length < 8) {
      setErrorMessage('Password must be at least 8 characters long');
      setLoading(false);
      return;
    }

    if (formData.password && formData.password !== formData.password_confirmation) {
      setErrorMessage('Passwords do not match');
      setLoading(false);
      return;
    }

    try {
      const updateData = {
        first_name: formData.first_name,
        last_name: formData.last_name,
        email: formData.email,
        department: formData.department || null,
      };

      // Only include password if it's provided
      if (formData.password && formData.password.trim() !== '') {
        updateData.password = formData.password;
      }

      const response = await API_CONFIG.request(`/api/users/${user.id}`, {
        method: 'PUT',
        body: JSON.stringify(updateData),
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || 'Failed to update profile');
      }

      const result = await response.json();
      
      // Update user context with new data
      setUser(result.user);
      
      // Clear password fields
      setFormData(prev => ({
        ...prev,
        password: '',
        password_confirmation: '',
      }));

      setSuccessMessage('Profile updated successfully!');
      
      // Clear success message after 5 seconds
      setTimeout(() => setSuccessMessage(''), 5000);
    } catch (error) {
      console.error('Error updating profile:', error);
      setErrorMessage(error.message || 'An error occurred while updating your profile');
    } finally {
      setLoading(false);
    }
  };

  // Show loading state while user context is loading
  if (userLoading) {
    return (
      <Layout>
        <div className="page-shell settings-page">
          <div className="page-grid wide">
            <div className="card">
              <div className="card-header">
                <span className="card-title">Settings</span>
              </div>
              <div className="card-body">
                <div className="muted">Loading...</div>
              </div>
            </div>
          </div>
        </div>
      </Layout>
    );
  }

  // If user is not logged in, show message (redirect will happen in useEffect)
  if (!user) {
    return null; // Will redirect in useEffect
  }

  return (
    <Layout>
      <div className="page-shell settings-page">
        <div className="page-grid wide">
          <div className="card">
            <div className="card-header">
              <span className="card-title">Settings</span>
              <span className="pill">/api/users/{user.id}</span>
            </div>
            <div className="card-body">
              {successMessage && <div className="pill success">{successMessage}</div>}
              {errorMessage && <div className="pill error">{errorMessage}</div>}
              <div className="muted">Update profile details and password.</div>
            </div>
          </div>

          <div className="card">
            <div className="card-header">
              <span className="card-title">Profile</span>
            </div>
            <div className="card-body">
              <form onSubmit={handleSubmit} className="settings-form">
                <div className="form-grid">
                  <label>
                    First Name
                    <input
                      type="text"
                      name="first_name"
                      value={formData.first_name}
                      onChange={handleChange}
                      required
                      disabled={loading}
                    />
                  </label>
                  <label>
                    Last Name
                    <input
                      type="text"
                      name="last_name"
                      value={formData.last_name}
                      onChange={handleChange}
                      required
                      disabled={loading}
                    />
                  </label>
                  <label>
                    Email
                    <input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      required
                      disabled={loading}
                    />
                  </label>
                  <label>
                    Department
                    <input
                      type="text"
                      name="department"
                      value={formData.department}
                      onChange={handleChange}
                      disabled={loading}
                      placeholder="Optional"
                    />
                  </label>
                  <label>
                    Role
                    <input
                      type="text"
                      value={user.role || 'N/A'}
                      disabled
                      className="disabled-input"
                    />
                  </label>
                </div>

                <div className="card-divider"></div>

                <div className="section-heading">Preferences</div>
                <div className="form-grid">
                  <label>
                    Notification Emails
                    <select disabled={loading}>
                      <option>Enabled</option>
                      <option>Disabled</option>
                    </select>
                  </label>
                  <label>
                    Dashboard Theme
                    <select disabled={loading}>
                      <option>Soft Academic</option>
                      <option>Dark Mode</option>
                      <option>Minimal</option>
                    </select>
                  </label>
                </div>

                <div className="card-divider"></div>

                <div className="section-heading">Change Password</div>
                <div className="muted">Leave blank if you don't want to change your password.</div>
                <div className="form-grid">
                  <label>
                    New Password
                    <input
                      type="password"
                      name="password"
                      value={formData.password}
                      onChange={handleChange}
                      disabled={loading}
                    />
                  </label>
                  <label>
                    Confirm New Password
                    <input
                      type="password"
                      name="password_confirmation"
                      value={formData.password_confirmation}
                      onChange={handleChange}
                      disabled={loading}
                    />
                  </label>
                </div>

                <div className="settings-actions">
                  <button 
                    type="submit" 
                    className="save-btn"
                    disabled={loading}
                  >
                    {loading ? 'Saving...' : 'Save Changes'}
                  </button>
                  <button
                    type="button"
                    className="cancel-btn"
                    onClick={() => {
                      setFormData({
                        first_name: user.first_name || '',
                        last_name: user.last_name || '',
                        email: user.email || '',
                        department: user.department || '',
                        password: '',
                        password_confirmation: '',
                      });
                      setErrorMessage('');
                      setSuccessMessage('');
                    }}
                    disabled={loading}
                  >
                    Reset
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default Settings;
