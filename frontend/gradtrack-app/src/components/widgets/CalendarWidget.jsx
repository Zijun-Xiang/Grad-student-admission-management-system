import React, { useState, useEffect, useContext, useMemo } from 'react';
import './CalendarWidget.css';
import API_CONFIG from '../../api/config';
import { UserContext } from '../../context/UserContext';

export default function CalendarWidget() {
  const [reminders, setReminders] = useState([]);
  const [loading, setLoading] = useState(true);
  const { user } = useContext(UserContext);

  useEffect(() => {
    const fetchReminders = async () => {
      if (!user) return;
      setLoading(true);
      
      try {
        const response = await API_CONFIG.request('/api/reminders', {
          method: 'GET',
        });
        if (response.ok) {
          const data = await response.json();
          setReminders(data || []);
        }
      } catch (error) {
        console.error('Error fetching reminders:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchReminders();
  }, [user]);

  const priorityCount = useMemo(() => {
    return reminders.reduce(
      (acc, r) => {
        const level = (r.priority || 'medium').toLowerCase();
        if (level === 'high') acc.high += 1;
        else if (level === 'low') acc.low += 1;
        else acc.medium += 1;
        return acc;
      },
      { high: 0, medium: 0, low: 0 }
    );
  }, [reminders]);

  const sortedReminders = useMemo(() => {
    return [...reminders].sort((a, b) => {
      const aDate = a.due_date ? new Date(a.due_date) : new Date(a.created_at || 0);
      const bDate = b.due_date ? new Date(b.due_date) : new Date(b.created_at || 0);
      return aDate - bDate;
    });
  }, [reminders]);

  return (
    <div className="card reminder-card">
      <div className="card-header">
        <span className="card-title">Reminders</span>
        <span className="pill">Total {reminders.length}</span>
      </div>
      <div className="card-body reminder-body">
        {loading ? (
          <div className="muted">Loading reminders...</div>
        ) : reminders.length === 0 ? (
          <div className="muted">No reminders yet.</div>
        ) : (
          <>
            <div className="reminder-stats">
              <div className="stat-chip high">High: {priorityCount.high}</div>
              <div className="stat-chip med">Medium: {priorityCount.medium}</div>
              <div className="stat-chip low">Low: {priorityCount.low}</div>
            </div>
            <ul className="reminder-list-mini">
              {sortedReminders.map((r) => (
                <li key={r.id} className={`priority-${r.priority || 'medium'}`}>
                  <div className="reminder-row">
                    <div>
                      <div className="reminder-text">{r.text}</div>
                      {r.created_by && (
                        <div className="reminder-meta">From {r.created_by.first_name} {r.created_by.last_name}</div>
                      )}
                    </div>
                    <div className="reminder-tags">
                      {r.due_date && (
                        <span className="badge">{new Date(r.due_date).toLocaleDateString()}</span>
                      )}
                      <span className={`badge priority ${r.priority || 'medium'}`}>{r.priority || 'medium'}</span>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          </>
        )}
      </div>
    </div>
  );
}
