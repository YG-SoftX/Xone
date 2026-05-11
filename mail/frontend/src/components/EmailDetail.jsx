import React, { useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { useMail } from '../context/MailContext'
import {
  Star, Trash2, Reply, Forward, MoreHorizontal, ArrowLeft,
  Paperclip, ChevronDown, ExternalLink
} from 'lucide-react'

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('en', {
    weekday: 'short', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function initials(name = '') {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() || '??'
}

export default function EmailDetail({ onReply }) {
  const { selected, setSelected, toggleStar, deleteEmail, moveToFolder, markRead } = useMail()
  const [showHeaders, setShowHeaders] = useState(false)
  const [menuOpen, setMenuOpen] = useState(false)

  if (!selected) {
    return (
      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 16, color: '#1e293b' }}>
        <motion.div
          animate={{ y: [0, -8, 0] }}
          transition={{ duration: 3, repeat: Infinity, ease: 'easeInOut' }}
          style={{ fontSize: 64 }}
        >
          ✉️
        </motion.div>
        <div style={{ fontSize: 15, fontWeight: 500, color: '#334155' }}>Select an email to read</div>
        <div style={{ fontSize: 13, color: '#1e293b' }}>Your conversations will appear here</div>
      </div>
    )
  }

  const email = selected
  const senderName = email.from_name || email.from_email?.split('@')[0] || 'Unknown'

  const menuActions = [
    { label: 'Move to Spam', onClick: () => { moveToFolder(email.id, 'spam'); setMenuOpen(false) } },
    { label: 'Mark as Unread', onClick: () => { markRead(email.id, false); setMenuOpen(false) } },
    { label: 'Move to Archive', onClick: () => { moveToFolder(email.id, 'archive'); setMenuOpen(false) } },
  ]

  return (
    <motion.div
      key={email.id}
      initial={{ opacity: 0, x: 20 }}
      animate={{ opacity: 1, x: 0 }}
      transition={{ duration: 0.25 }}
      style={{ flex: 1, display: 'flex', flexDirection: 'column', height: '100%', overflow: 'hidden', background: 'rgba(7,8,13,0.4)' }}
    >
      {/* Toolbar */}
      <div style={{ flexShrink: 0, display: 'flex', alignItems: 'center', gap: 8, padding: '12px 20px', borderBottom: '1px solid rgba(255,255,255,0.06)' }}>
        <motion.button
          whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
          onClick={() => setSelected(null)}
          style={{ width: 32, height: 32, borderRadius: 9, background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.08)', cursor: 'pointer', color: '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
        >
          <ArrowLeft size={14} />
        </motion.button>

        <div style={{ flex: 1 }} />

        {/* Action buttons */}
        {[
          { icon: Reply, label: 'Reply', onClick: () => onReply(email), color: '#6366f1' },
          { icon: Forward, label: 'Forward', onClick: () => onReply({ ...email, subject: `Fwd: ${email.subject}`, to: '' }), color: '#8b5cf6' },
        ].map(({ icon: Icon, label, onClick, color }) => (
          <motion.button key={label} whileHover={{ scale: 1.05, y: -1 }} whileTap={{ scale: 0.95 }}
            onClick={onClick}
            style={{ display: 'flex', alignItems: 'center', gap: 6, padding: '7px 14px', borderRadius: 10, background: `${color}15`, border: `1px solid ${color}30`, cursor: 'pointer', color, fontSize: 13, fontWeight: 600, fontFamily: 'inherit' }}
          >
            <Icon size={13} />
            {label}
          </motion.button>
        ))}

        <motion.button whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
          onClick={() => toggleStar(email.id)}
          style={{ width: 32, height: 32, borderRadius: 9, background: email.is_starred ? 'rgba(245,158,11,0.12)' : 'rgba(255,255,255,0.05)', border: `1px solid ${email.is_starred ? 'rgba(245,158,11,0.3)' : 'rgba(255,255,255,0.08)'}`, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
        >
          <Star size={14} fill={email.is_starred ? '#f59e0b' : 'none'} color={email.is_starred ? '#f59e0b' : '#64748b'} />
        </motion.button>

        <motion.button whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
          onClick={() => deleteEmail(email.id)}
          style={{ width: 32, height: 32, borderRadius: 9, background: 'rgba(239,68,68,0.08)', border: '1px solid rgba(239,68,68,0.2)', cursor: 'pointer', color: '#f87171', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
        >
          <Trash2 size={14} />
        </motion.button>

        {/* More menu */}
        <div style={{ position: 'relative' }}>
          <motion.button whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
            onClick={() => setMenuOpen(m => !m)}
            style={{ width: 32, height: 32, borderRadius: 9, background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.08)', cursor: 'pointer', color: '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
          >
            <MoreHorizontal size={14} />
          </motion.button>
          <AnimatePresence>
            {menuOpen && (
              <motion.div
                initial={{ opacity: 0, y: 8, scale: 0.95 }} animate={{ opacity: 1, y: 0, scale: 1 }} exit={{ opacity: 0, y: 4, scale: 0.95 }}
                style={{ position: 'absolute', right: 0, top: '100%', marginTop: 6, width: 180, background: '#0f1120', border: '1px solid rgba(255,255,255,0.1)', borderRadius: 12, padding: 6, zIndex: 50, boxShadow: '0 16px 48px rgba(0,0,0,0.6)' }}
              >
                {menuActions.map(({ label, onClick }) => (
                  <button key={label} onClick={onClick}
                    style={{ width: '100%', padding: '9px 12px', borderRadius: 8, background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', fontSize: 13, fontFamily: 'inherit', textAlign: 'left', transition: 'background 0.15s' }}
                    onMouseEnter={e => e.target.style.background = 'rgba(255,255,255,0.06)'}
                    onMouseLeave={e => e.target.style.background = 'none'}
                  >
                    {label}
                  </button>
                ))}
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      </div>

      {/* Email content */}
      <div style={{ flex: 1, overflowY: 'auto', padding: '28px 36px' }}>
        {/* Subject */}
        <motion.h1 initial={{ opacity: 0, y: -8 }} animate={{ opacity: 1, y: 0 }}
          style={{ fontSize: 22, fontWeight: 800, color: '#f1f5f9', marginBottom: 20, letterSpacing: '-0.3px', lineHeight: 1.3 }}>
          {email.subject || '(no subject)'}
        </motion.h1>

        {/* Sender info */}
        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: 0.08 }}
          style={{ display: 'flex', alignItems: 'flex-start', gap: 14, marginBottom: 24, padding: '16px 20px', background: 'rgba(255,255,255,0.025)', borderRadius: 16, border: '1px solid rgba(255,255,255,0.06)' }}>
          <div style={{
            width: 44, height: 44, borderRadius: '50%', flexShrink: 0,
            background: 'linear-gradient(135deg,#6366f1,#8b5cf6)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontSize: 14, fontWeight: 700, color: '#fff',
          }}>
            {initials(senderName)}
          </div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 4 }}>
              <div>
                <span style={{ fontSize: 14, fontWeight: 700, color: '#f1f5f9' }}>{senderName}</span>
                <span style={{ fontSize: 12, color: '#475569', marginLeft: 8 }}>&lt;{email.from_email}&gt;</span>
              </div>
              <span style={{ fontSize: 12, color: '#334155', flexShrink: 0 }}>{formatDate(email.received_at || email.created_at)}</span>
            </div>

            <button onClick={() => setShowHeaders(h => !h)}
              style={{ display: 'flex', alignItems: 'center', gap: 4, background: 'none', border: 'none', cursor: 'pointer', color: '#475569', fontSize: 12, padding: 0, fontFamily: 'inherit' }}>
              <span>to {Array.isArray(email.to) ? email.to.join(', ') : email.to}</span>
              <ChevronDown size={12} style={{ transform: showHeaders ? 'rotate(180deg)' : 'none', transition: 'transform 0.2s' }} />
            </button>

            <AnimatePresence>
              {showHeaders && (
                <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: 'auto', opacity: 1 }} exit={{ height: 0, opacity: 0 }}
                  style={{ overflow: 'hidden' }}>
                  <div style={{ marginTop: 8, display: 'flex', flexDirection: 'column', gap: 4 }}>
                    {email.cc && (
                      <div style={{ fontSize: 12, color: '#475569' }}>
                        <span style={{ color: '#334155' }}>cc: </span>
                        {Array.isArray(email.cc) ? email.cc.join(', ') : email.cc}
                      </div>
                    )}
                    <div style={{ fontSize: 12, color: '#334155' }}>
                      Message-ID: {email.message_id}
                    </div>
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </motion.div>

        {/* Body */}
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.12 }}>
          {email.body_html ? (
            <div
              style={{ color: '#cbd5e1', fontSize: 14, lineHeight: 1.7 }}
              dangerouslySetInnerHTML={{ __html: email.body_html }}
            />
          ) : (
            <pre style={{ color: '#cbd5e1', fontSize: 14, lineHeight: 1.7, whiteSpace: 'pre-wrap', fontFamily: 'inherit', margin: 0 }}>
              {email.body_text || '(empty message)'}
            </pre>
          )}
        </motion.div>

        {/* Attachments */}
        {email.attachments?.length > 0 && (
          <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: 0.2 }}
            style={{ marginTop: 28, paddingTop: 20, borderTop: '1px solid rgba(255,255,255,0.06)' }}>
            <div style={{ fontSize: 12, fontWeight: 600, color: '#475569', textTransform: 'uppercase', letterSpacing: '0.8px', marginBottom: 12 }}>
              Attachments ({email.attachments.length})
            </div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10 }}>
              {email.attachments.map((att, i) => (
                <motion.a key={i} href={att.url} target="_blank" rel="noopener noreferrer"
                  whileHover={{ y: -2, borderColor: 'rgba(99,102,241,0.4)' }}
                  style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '8px 14px', borderRadius: 10, background: 'rgba(255,255,255,0.04)', border: '1px solid rgba(255,255,255,0.08)', textDecoration: 'none', color: '#94a3b8', fontSize: 13 }}>
                  <Paperclip size={13} />
                  {att.name || `Attachment ${i + 1}`}
                  <ExternalLink size={11} style={{ opacity: 0.5 }} />
                </motion.a>
              ))}
            </div>
          </motion.div>
        )}
      </div>

      {/* Quick reply */}
      <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }}
        style={{ flexShrink: 0, padding: '12px 24px 20px', borderTop: '1px solid rgba(255,255,255,0.06)' }}>
        <motion.button
          onClick={() => onReply(email)}
          whileHover={{ scale: 1.01, boxShadow: '0 4px 20px rgba(99,102,241,0.25)' }}
          whileTap={{ scale: 0.98 }}
          style={{ width: '100%', padding: '12px 20px', borderRadius: 14, background: 'rgba(99,102,241,0.08)', border: '1px solid rgba(99,102,241,0.2)', cursor: 'pointer', color: '#818cf8', fontSize: 13, fontFamily: 'inherit', textAlign: 'left', display: 'flex', alignItems: 'center', gap: 10 }}
        >
          <Reply size={14} />
          Reply to {senderName}…
        </motion.button>
      </motion.div>
    </motion.div>
  )
}
