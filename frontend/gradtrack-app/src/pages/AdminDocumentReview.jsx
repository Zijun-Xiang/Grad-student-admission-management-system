import React, { useState, useEffect } from 'react';
import Layout from '../components/layout/Layout';
import './AdminDocumentReview.css';
import documentVaultApi from '../features/DocumentVault/api/documentVaultApi';

const AdminDocumentReview = () => {
  const [documents, setDocuments] = useState([]);
  const [searchString, setSearchString] = useState('');
  const [loading, setLoading] = useState(true);
  const [showDeclineModal, setShowDeclineModal] = useState(false);
  const [selectedDocument, setSelectedDocument] = useState(null);
  const [declineReason, setDeclineReason] = useState('');
  const [updating, setUpdating] = useState(false);

  useEffect(() => {
    loadDocuments();
  }, []);

  const loadDocuments = async () => {
    try {
      setLoading(true);
      const docs = await documentVaultApi.getAllDocumentsForReview();
      // Filter to only show documents with "Pending Review" status
      const pendingDocs = (docs || []).filter(doc => doc.status === 'Pending Review');
      const mapped = pendingDocs.map(doc => ({
        id: doc.id,
        name: doc.file_name,
        url: doc.file_path,
        size: formatFileSize(doc.file_size),
        type: doc.file_type,
        date: new Date(doc.created_at).toLocaleDateString(),
        tag: doc.tag,
        documentType: doc.required_document_type || doc.tag || 'Other',
        status: doc.status,
        uploadedBy: doc.uploaded_by,
        reviewComment: doc.review_comment || null,
        userId: doc.user_id,
      }));
      setDocuments(mapped);
    } catch (error) {
      console.error('Error loading documents:', error);
      alert('Failed to load documents. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const handleDownload = async (id, name) => {
    try {
      await documentVaultApi.downloadDocument(id, name);
    } catch (error) {
      console.error('Download failed:', error);
      alert('Failed to download document');
    }
  };

  const handleApprove = async (doc) => {
    if (!confirm(`Are you sure you want to approve "${doc.name}"?`)) {
      return;
    }

    try {
      setUpdating(true);
      await documentVaultApi.updateDocumentStatus(doc.id, 'Approved');
      alert('Document approved successfully');
      await loadDocuments(); // Reload documents to show updated status
    } catch (error) {
      console.error('Error approving document:', error);
      alert('Failed to approve document. Please try again.');
    } finally {
      setUpdating(false);
    }
  };

  const handleDeclineClick = (doc) => {
    setSelectedDocument(doc);
    setDeclineReason('');
    setShowDeclineModal(true);
  };

  const handleDeclineSubmit = async () => {
    if (!declineReason.trim()) {
      alert('Please provide a reason for declining this document.');
      return;
    }

    if (!selectedDocument) return;

    try {
      setUpdating(true);
      await documentVaultApi.updateDocumentStatus(
        selectedDocument.id,
        'Declined',
        declineReason.trim()
      );
      alert('Document declined successfully');
      setShowDeclineModal(false);
      setSelectedDocument(null);
      setDeclineReason('');
      await loadDocuments(); // Reload documents to show updated status
    } catch (error) {
      console.error('Error declining document:', error);
      alert('Failed to decline document. Please try again.');
    } finally {
      setUpdating(false);
    }
  };

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'Approved':
        return 'status-badge approved';
      case 'Declined':
        return 'status-badge declined';
      case 'Pending Review':
        return 'status-badge pending';
      default:
        return 'status-badge';
    }
  };

  const filteredDocs = documents.filter(doc =>
    doc.name.toLowerCase().includes(searchString.toLowerCase())
  );

  return (
    <Layout>
      <div className="page-shell admin-document-review-page">
        <div className="page-grid wide">
          <div className="card">
            <div className="card-header">
              <span className="card-title">Document Review Queue</span>
              <span className="pill">documentVaultApi</span>
            </div>
            <div className="card-body">
              <div className="muted">Review and manage all uploaded documents across the system</div>
              <input
                type="text"
                placeholder="Search by file name..."
                className="search-input"
                value={searchString}
                onChange={(e) => setSearchString(e.target.value)}
              />
            </div>
          </div>

          <div className="card">
            <div className="card-header">
              <span className="card-title">Pending Documents</span>
              <span className="pill">{filteredDocs.length}</span>
            </div>
            <div className="card-body">
              {loading ? (
                <div className="muted">Loading documents...</div>
              ) : (
                <div className="document-list">
                  {filteredDocs.length === 0 ? (
                    <div className="muted">No documents found.</div>
                  ) : (
                    filteredDocs.map(doc => (
                      <div key={doc.id} className="document-card">
                        <div className="doc-info">
                          <h4>{doc.name}</h4>
                          <p><strong>Document Type:</strong> {doc.documentType}</p>
                          <p><strong>Uploaded by:</strong> {doc.uploadedBy}</p>
                          <p><strong>Date:</strong> {doc.date}</p>
                          <p><strong>Size:</strong> {doc.size}</p>
                          <p>
                            <strong>Status:</strong>{' '}
                            <span className={getStatusBadgeClass(doc.status)}>
                              {doc.status}
                            </span>
                          </p>
                          {doc.reviewComment && doc.status === 'Declined' && (
                            <div className="review-comment">
                              <strong>Decline Reason:</strong>
                              <p className="comment-text">{doc.reviewComment}</p>
                            </div>
                          )}
                        </div>
                        <div className="doc-actions">
                          <button onClick={() => handleDownload(doc.id, doc.name)}>
                            Download
                          </button>
                          {doc.status === 'Pending Review' && (
                            <>
                              <button
                                className="approve-btn"
                                onClick={() => handleApprove(doc)}
                                disabled={updating}
                              >
                                Approve
                              </button>
                              <button
                                className="decline-btn"
                                onClick={() => handleDeclineClick(doc)}
                                disabled={updating}
                              >
                                Decline
                              </button>
                            </>
                          )}
                        </div>
                      </div>
                    ))
                  )}
                </div>
              )}
            </div>
          </div>
        </div>

        {showDeclineModal && (
          <div className="modal-overlay" onClick={() => setShowDeclineModal(false)}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
              <h3>Decline Document</h3>
              <p>
                <strong>Document:</strong> {selectedDocument?.name}
              </p>
              <label>
                <strong>Reason for Decline (Required):</strong>
                <textarea
                  value={declineReason}
                  onChange={(e) => setDeclineReason(e.target.value)}
                  placeholder="Please provide a reason for declining this document..."
                  rows="5"
                  maxLength={1000}
                  className="decline-reason-input"
                />
                <span className="char-count">
                  {declineReason.length}/1000 characters
                </span>
              </label>
              <div className="modal-actions">
                <button
                  className="cancel-btn"
                  onClick={() => {
                    setShowDeclineModal(false);
                    setSelectedDocument(null);
                    setDeclineReason('');
                  }}
                  disabled={updating}
                >
                  Cancel
                </button>
                <button
                  className="submit-decline-btn"
                  onClick={handleDeclineSubmit}
                  disabled={updating || !declineReason.trim()}
                >
                  {updating ? 'Submitting...' : 'Submit Decline'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </Layout>
  );
};

export default AdminDocumentReview;
