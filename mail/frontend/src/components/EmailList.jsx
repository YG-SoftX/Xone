import React from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { useMail } from '../context/MailContext'
import { Star, Paperclip, ChevronLeft, ChevronRight, RefreshCw } from 'lucide-react'

function timeAgo(dateStr) {
  const d = new Date(dateStr)
  const now = new Date()
  const diff = (now - d) / 1000
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h`
  if (diff < 604800) {
    return d.toLocaleDateString('en', { weekday: 'short' })
  }
  return d.toLocaleDateString('en', { month: 'short', day: 'numeric' })
}

function initials(name = '') {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() || '??'
}

const avatarColors = ['#6366f1','#8b5cf6','#ef4444','#f59e0b','#10b981','#3b82f6','#ec4899']
function avatarColor(str = '') {
  let h = 0
  for (let i = 0; i < str.length; i++) h = str.charCodeAt(i) + ((h << 5) - h)
  return avatarColors[Math.abs(h) % avatarColors.length]
}

export default function EmailList({ onCompose }) {
  const { emails, selected, openEmail, toggleStar, folder, pagination, switchFolder, loading } = useMail()

  const folderLabel = folder.charAt(0).toUpperCase() + folder.slice(1)

  return (
    <div style={{
      width: 340, flexShrink: 0, height: '100%', display: 'flex', flexDirection: 'column',
      borderRight: '1px solid rgba(255,255,255,0.06)', background: 'rgba(7,8,13,0.6)',
    }}>
      {/* Header */}
      <div style={{ flexShrink: 0, padding: '16px 18px 12px', borderBottom: '1px solid rgba(255,255,255,0.06)' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <h2 style={{ fontSize: 16, fontWeight: 700, color: '#f1f5f9', margin: 0 }}>{folderLabel}</h2>
          <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
            {pagination && (
              <span style={{ fontSize: 12, color: '#475569' }}>
                {pagination.from}–{pagination.to} of {pagination.total}
              </span>
            )}
            <motion.button
              whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
              onClick={() => switchFolder(folder)}
              style={{ width: 28, height: 28, borderRadius: 8, background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.08)', cursor: 'pointer', color: '#475569', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
            >
              <RefreshCw size={12} />
            </motion.button>
          </div>
        </div>
      </div>

      {/* List */}
      <div style={{ flex: 1, overflowY: 'auto' }}>
        {loading ? (
          <div style={{ padding: 16, display: 'flex', flexDirection: 'column', gap: 10 }}>
            {[...Array(6)].map((_, i) => (
              <motion.div key={i} animate={{ opacity: [0.4, 0.7, 0.4] }} transition={{ duration: 1.4, repeat: Infinity, delay: i * 0.1 }}
                style={{ height: 72, borderRadius: 12, background: 'rgba(255,255,255,0.04)' }} />
            ))}
          </div>
        ) : emails.length === 0 ? (
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '60%', gap: 12, color: '#334155' }}>
            <div style={{ fontSize: 40 }}>📭</div>
            <div style={{ fontSize: 14, fontWeight: 500 }}>No messages here</div>
          </div>
        ) : (
          <AnimatePresence>
            {emails.map((email, i) => {
              const isSelected = selected?.id === email.id
              const isUnread = !email.is_read
              const senderName = email.from_name || email.from_email?.split('@')[0] || 'Unknown'
              const color = avatarColor(senderName)

              return (
                <motion.div
                  key={email.id}
                  initial={{ opacity: 0, y: 8 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, x: -20 }}
                  transition={{ delay: i * 0.03 }}
                  onClick={() => openEmail(email)}
                  style={{
                    display: 'flex', alignItems: 'flex-start', gap: 12, padding: '12px 16px',
                    cursor: 'pointer', borderBottom: '1px solid rgba(255,255,255,0.04)',
                    background: isSelected
                      ? 'rgba(239,68,68,0.08)'
                      : isUnread
                        ? 'rgba(255,255,255,0.025)'
                        : 'transparent',
                    borderLeft: isSelected ? '3px solid #ef4444' : '3px solid transparent',
                    transition: 'all 0.15s ease',
                    position: 'relative',
                  }}
                  whileHover={{ background: isSelected ? 'rgba(239,68,68,0.1)' : 'rgba(255,255,255,0.04)' }}
                >
                  {/* Avatar */}
                  <div style={{
                    width: 36, height: 36, borderRadius: '50%', flexShrink: 0,
                    background: `${color}25`, border: `1px solid ${color}40`,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    fontSize: 12, fontWeight: 700, color,
                  }}>
                    {initials(senderName)}
                  </div>

                  {/* Content */}
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 3 }}>
                      <span style={{ fontSize: 13, fontWeight: isUnread ? 700 : 500, color: isUnread ? '#f1f5f9' : '#94a3b8', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: 160 }}>
                        {senderName}
                      </span>
                      <span style={{ fontSize: 11, color: '#334155', flexShrink: 0, marginLeft: 8 }}>
                        {timeAgo(email.received_at || email.created_at)}
                      </span>
                    </div>
                    <div style={{ fontSize: 12.5, fontWeight: isUnread ? 600 : 400, color: isUnread ? '#e2e8f0' : '#64748b', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', marginBottom: 3 }}>
                      {email.subject || '(no subject)'}
                    </div>
                    <div style={{ fontSize: 12, color: '#334155', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                      {email.preview || email.body_text?.slice(0, 80) || ''}
                    </div>

                    {/* Indicators */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 5 }}>
                      {email.has_attachments && <Paperclip size={11} color="#475569" />}
                      {isUnread && <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#ef4444', display: 'inline-block' }} />}
                    </div>
                  </div>

                  {/* Star */}
                  <motion.button
                    whileHover={{ scale: 1.2 }} whileTap={{ scale: 0.8 }}
                    onClick={e => { e.stopPropagation(); toggleStar(email.id) }}
                    style={{ background: 'none', border: 'none', cursor: 'pointer', padding: 2, marginTop: 2, flexShrink: 0 }}
                  >
                    <Star size={13} fill={email.is_starred ? '#f59e0b' : 'none'} color={email.is_starred ? '#f59e0b' : '#334155'} />
                  </motion.button>
                </motion.div>
              )
            })}
          </AnimatePresence>
        )}
      </div>

      {/* Pagination */}
      {pagination && pagination.last_page > 1 && (
        <div style={{ flexShrink: 0, padding: '10px 16px', borderTop: '1px solid rgba(255,255,255,0.06)', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
          <motion.button whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
            disabled={pagination.current_page <= 1}
            onClick={() => switchFolder(folder, pagination.current_page - 1)}
            style={{ width: 30, height: 30, borderRadius: 8, background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.08)', cursor: 'pointer', color: pagination.current_page <= 1 ? '#1e293b' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
          >
            <ChevronLeft size={14} />
          </motion.button>
          <span style={{ fontSize: 12, color: '#475569' }}>{pagination.current_page} / {pagination.last_page}</span>
          <motion.button whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
            disabled={pagination.current_page >= pagination.last_page}
            onClick={() => switchFolder(folder, pagination.current_page + 1)}
            style={{ width: 30, height: 30, borderRadius: 8, background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.08)', cursor: 'pointer', color: pagination.current_page >= pagination.last_page ? '#1e293b' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
          >
            <ChevronRight size={14} />
          </motion.button>
        </div>
      )}
    </div>
  )
}
