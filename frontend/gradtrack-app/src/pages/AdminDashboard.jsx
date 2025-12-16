import React, { useEffect, useState, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../components/layout/Layout';
import CalendarWidget from '../components/widgets/CalendarWidget';
import DocumentReview from '../components/widgets/AdminDocumentReviewWidget';
import API_CONFIG from '../api/config';
import { UserContext } from '../context/UserContext';
import './AdminDashboard.css';

const AdminDashboard = () => {
  const [students, setStudents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const navigate = useNavigate();
  const { user, loading: userLoading } = useContext(UserContext);

  useEffect(() => {
    const fetchStudents = async () => {
      setLoading(true);
      setError(null);
      
      try {
        // Fetch all students
        const response = await API_CONFIG.request('/api/students', {
          method: 'GET',
        });
        const data = await response.json();
        setStudents(data.students || data || []);
      } catch (error) {
        console.error('Error fetching students:', error);
        setError('Failed to load students. Please try again.');
      } finally {
        setLoading(false);
      }
    };

    if (!userLoading && user && user.role === 'admin') {
      fetchStudents();
    }
  }, [userLoading, user]);

  const handleStudentClick = (studentId) => {
    navigate(`/student-details/${studentId}`);
  };

  useEffect(() => {
    // Redirect non-admin users
    if (!userLoading && user && user.role !== 'admin') {
      navigate('/dashboard');
    }
  }, [user, userLoading, navigate]);

  if (userLoading || loading) return <Layout><div className="loading-message">Loading...</div></Layout>;
  if (error) return <Layout><div className="error-message">{error}</div></Layout>;
  if (!user) return <Layout><div className="error-message">Please log in to access the admin dashboard.</div></Layout>;
  if (user.role !== 'admin') return <Layout><div className="error-message">Access denied. Admin privileges required.</div></Layout>;

  return (
    <Layout>
      <div className="page-shell admin-dashboard-page">
        <div className="page-grid wide admin-grid">
          <div className="card">
            <div className="card-header">
              <span className="card-title">Admin Overview</span>
              <span className="pill">Admin</span>
            </div>
            <div className="card-body">
              <div className="section-heading">Welcome, {user.first_name} {user.last_name}</div>
              <div className="muted">System Administrator</div>
            </div>
          </div>

          <CalendarWidget className="grid-span-2 reminder-prominent" />

          <div className="card">
            <div className="card-header">
              <span className="card-title">Document Review</span>
              <span className="pill">/api/documents</span>
            </div>
            <div className="card-body card-body-flush">
              <DocumentReview />
            </div>
          </div>

          <div className="card table-card">
            <div className="card-header">
              <span className="card-title">Student Management</span>
              <span className="pill">/api/students</span>
            </div>
            <div className="card-body">
              {students.length === 0 ? (
                <div className="muted">No students found</div>
              ) : (
                <div className="students-table-container">
                  <table className="students-table">
                    <thead>
                      <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Program Type</th>
                        <th>Department</th>
                        <th>Advisor</th>
                        <th>I9 Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {students.map((student) => (
                        <tr 
                          key={student.student_id} 
                          className="student-row"
                          onClick={() => handleStudentClick(student.student_id)}
                        >
                          <td>{student.student_id}</td>
                          <td>{student.user?.first_name} {student.user?.last_name}</td>
                          <td>{student.user?.email}</td>
                          <td>{student.program_type || 'N/A'}</td>
                          <td>{student.user?.department || 'N/A'}</td>
                          <td>
                            {student.major_professor 
                              ? `${student.major_professor.first_name} ${student.major_professor.last_name}`
                              : 'Not Assigned'}
                          </td>
                          <td>
                            <span className={`status-badge ${student.i9_status?.toLowerCase() || 'pending'}`}>
                              {student.i9_status || 'Pending'}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default AdminDashboard;
