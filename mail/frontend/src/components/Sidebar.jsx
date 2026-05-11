import React from 'react'
import { motion } from 'framer-motion'
import { useMail } from '../context/MailContext'
import {
  Inbox, Star, Send, FileText, AlertTriangle, Trash2,
  Plus, Settings, ChevronRight
} from 'lucide-react'

const folders = [
  { id: 'inbox',   label: 'Inbox',   icon: Inbox,         color: '#ef4444' },
  { id: 'starred', label: 'Starred', icon: Star,           color: '#f59e0b' },
  { id: 'sent',    label: 'Sent',    icon: Send,           color: '#6366f1' },
  { id: 'drafts',  label: 'Drafts',  icon: FileText,       color: '#8b5cf6' },
  { id: 'spam',    label: 'Spam',    icon: AlertTriangle,  color: '#f97316' },
  { id: 'trash',   label: 'Trash',   icon: Trash2,         color: '#475569' },
]

export default function Sidebar({ onCompose, onPanel, activePanel }) {
  const { folder, switchFolder, unread } = useMail()

  const handleFolder = (id) => {
    switchFolder(id)
    if (activePanel !== 'list') onPanel('list')
  }

  return (
    <motion.aside
      initial={{ x: -20, opacity: 0 }}
      animate={{ x: 0, opacity: 1 }}
      transition={{ duration: 0.35 }}
      style={{
        width: 220, flexShrink: 0, height: '100%',
        background: 'rgba(8,9,20,0.9)', borderRight: '1px solid rgba(255,255,255,0.06)',
        backdropFilter: 'blur(20px)', display: 'flex', flexDirection: 'column',
        overflowY: 'auto', overflowX: 'hidden',
      }}
    >
      {/* Compose */}
      <div style={{ padding: '20px 14px 12px' }}>
        <motion.button
          onClick={onCompose}
          whileHover={{ scale: 1.02, boxShadow: '0 6px 24px rgba(239,68,68,0.45)' }}
          whileTap={{ scale: 0.97 }}
          style={{
            width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
            padding: '11px 0', borderRadius: 14,
            background: 'linear-gradient(135deg,#ef4444,#f97316)',
            border: 'none', color: '#fff', fontSize: 14, fontWeight: 700,
            cursor: 'pointer', fontFamily: 'inherit',
            boxShadow: '0 4px 16px rgba(239,68,68,0.35)',
          }}
        >
          <Plus size={16} strokeWidth={2.5} />
          Compose
        </motion.button>
      </div>

      {/* Folders */}
      <nav style={{ flex: 1, padding: '4px 10px', display: 'flex', flexDirection: 'column', gap: 2 }}>
        <div style={{ fontSize: 10, fontWeight: 600, color: '#334155', textTransform: 'uppercase', letterSpacing: '1px', padding: '4px 8px 8px' }}>
          Folders
        </div>

        {folders.map(({ id, label, icon: Icon, color }, i) => {
          const isActive = folder === id && activePanel === 'list'
          const count = id === 'inbox' ? unread : 0

          return (
            <motion.button
              key={id}
              onClick={() => handleFolder(id)}
              initial={{ opacity: 0, x: -10 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ delay: i * 0.04 }}
              whileHover={{ x: 3 }}
              style={{
                width: '100%', display: 'flex', alignItems: 'center', gap: 10,
                padding: '9px 12px', borderRadius: 11, border: 'none', cursor: 'pointer',
                background: isActive ? 'rgba(239,68,68,0.1)' : 'transparent',
                borderLeft: isActive ? `2px solid ${color}` : '2px solid transparent',
                color: isActive ? '#f87171' : '#64748b',
                fontSize: 13.5, fontWeight: isActive ? 600 : 500,
                fontFamily: 'inherit', transition: 'all 0.15s ease',
                textAlign: 'left',
              }}
            >
              <Icon size={15} color={isActive ? color : undefined} />
              <span style={{ flex: 1 }}>{label}</span>
              {count > 0 && (
                <span style={{
                  fontSize: 11, fontWeight: 700, padding: '2px 7px', borderRadius: 999,
                  background: 'rgba(239,68,68,0.15)', color: '#f87171',
                  minWidth: 20, textAlign: 'center',
                }}>
                  {count > 99 ? '99+' : count}
                </span>
              )}
              {isActive && <ChevronRight size={12} style={{ opacity: 0.5 }} />}
            </motion.button>
          )
        })}
      </nav>

      {/* Bottom */}
      <div style={{ padding: '8px 10px 16px', borderTop: '1px solid rgba(255,255,255,0.06)' }}>
        <motion.button
          onClick={() => onPanel(activePanel === 'accounts' ? 'list' : 'accounts')}
          whileHover={{ x: 3 }}
          style={{
            width: '100%', display: 'flex', alignItems: 'center', gap: 10,
            padding: '9px 12px', borderRadius: 11, border: 'none', cursor: 'pointer',
            background: activePanel === 'accounts' ? 'rgba(99,102,241,0.1)' : 'transparent',
            color: activePanel === 'accounts' ? '#818cf8' : '#475569',
            fontSize: 13.5, fontWeight: 500, fontFamily: 'inherit',
            transition: 'all 0.15s ease', textAlign: 'left',
          }}
        >
          <Settings size={15} />
          Accounts
        </motion.button>
      </div>
    </motion.aside>
  )
}
