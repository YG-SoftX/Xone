import React, { useState, useEffect, useRef } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { useMail } from '../context/MailContext'
import { X, Send, Minimize2, Maximize2, Trash2, ChevronDown } from 'lucide-react'

export default function Compose({ open, onClose, replyTo }) {
  const { sendEmail, accounts, saveDraft } = useMail()

  const [from, setFrom]       = useState('')
  const [to, setTo]           = useState('')
  const [cc, setCc]           = useState('')
  const [subject, setSubject] = useState('')
  const [body, setBody]       = useState('')
  const [showCc, setShowCc]   = useState(false)
  const [sending, setSending] = useState(false)
  const [minimized, setMinimized] = useState(false)
  const [sent, setSent]       = useState(false)

  const bodyRef = useRef(null)

  // Populate fields when replying
  useEffect(() => {
    if (replyTo) {
      setTo(replyTo.from_email || '')
      setSubject(replyTo.subject?.startsWith('Re:') ? replyTo.subject : `Re: ${replyTo.subject || ''}`)
      setBody(`\n\n--- Original message from ${replyTo.from_name || replyTo.from_email} ---\n${replyTo.body_text || ''}`)
    } else {
      setTo(''); setSubject(''); setBody(''); setCc('')
    }
    setSent(false)
    setMinimized(false)
  }, [replyTo, open])

  // Set default from address
  useEffect(() => {
    if (accounts?.length) {
      const primary = accounts.find(a => a.is_primary) || accounts[0]
      setFrom(primary?.email_address || '')
    }
  }, [accounts])

  const handleSend = async () => {
    if (!to.trim() || !subject.trim()) return
    setSending(true)
    try {
      await sendEmail({ from, to, cc: cc || undefined, subject, body })
      setSent(true)
      setTimeout(() => { onClose(); setSent(false) }, 1200)
    } catch (err) {
      console.error('Send failed', err)
    } finally {
      setSending(false)
    }
  }

  const handleDiscard = () => {
    if (body.trim() || subject.trim()) {
      saveDraft?.({ from, to, cc, subject, body })
    }
    onClose()
  }

  const inputStyle = {
    width: '100%', background: 'none', border: 'none', outline: 'none',
    color: '#e2e8f0', fontSize: 13.5, fontFamily: 'inherit', padding: '10px 0',
    borderBottom: '1px solid rgba(255,255,255,0.06)',
  }

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          initial={{ opacity: 0, y: 40, scale: 0.95 }}
          animate={minimized
            ? { opacity: 1, y: 0, scale: 1, height: 52 }
            : { opacity: 1, y: 0, scale: 1, height: 'auto' }
          }
          exit={{ opacity: 0, y: 40, scale: 0.95 }}
          transition={{ duration: 0.22, ease: [0.25, 0.46, 0.45, 0.94] }}
          style={{
            position: 'fixed', bottom: 24, right: 24, width: 520,
            background: '#0d0f1e', border: '1px solid rgba(255,255,255,0.12)',
            borderRadius: 20, overflow: 'hidden', zIndex: 100,
            boxShadow: '0 24px 80px rgba(0,0,0,0.8), 0 0 0 1px rgba(99,102,241,0.1)',
          }}
        >
          {/* Header */}
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: 'linear-gradient(135deg,rgba(99,102,241,0.15),rgba(139,92,246,0.1))', borderBottom: minimized ? 'none' : '1px solid rgba(255,255,255,0.06)', cursor: minimized ? 'pointer' : 'default' }}
            onClick={minimized ? () => setMinimized(false) : undefined}>
            <span style={{ fontSize: 13.5, fontWeight: 700, color: '#e2e8f0' }}>
              {replyTo ? `Reply to ${replyTo.from_name || replyTo.from_email}` : 'New Message'}
            </span>
            <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
              <motion.button whileHover={{ scale: 1.15 }} whileTap={{ scale: 0.85 }}
                onClick={e => { e.stopPropagation(); setMinimized(m => !m) }}
                style={{ width: 26, height: 26, borderRadius: 7, background: 'rgba(255,255,255,0.06)', border: 'none', cursor: 'pointer', color: '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                {minimized ? <Maximize2 size={12} /> : <Minimize2 size={12} />}
              </motion.button>
              <motion.button whileHover={{ scale: 1.15 }} whileTap={{ scale: 0.85 }}
                onClick={handleDiscard}
                style={{ width: 26, height: 26, borderRadius: 7, background: 'rgba(255,255,255,0.06)', border: 'none', cursor: 'pointer', color: '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <X size={13} />
              </motion.button>
            </div>
          </div>

          {!minimized && (
            <div style={{ padding: '0 18px' }}>
              {/* From */}
              {accounts?.length > 1 ? (
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, ...inputStyle, paddingLeft: 0, paddingRight: 0 }}>
                  <span style={{ fontSize: 12, color: '#475569', width: 52, flexShrink: 0 }}>From</span>
                  <select value={from} onChange={e => setFrom(e.target.value)}
                    style={{ flex: 1, background: 'none', border: 'none', outline: 'none', color: '#e2e8f0', fontSize: 13.5, fontFamily: 'inherit', cursor: 'pointer' }}>
                    {accounts.map(a => (
                      <option key={a.id} value={a.email_address} style={{ background: '#0d0f1e' }}>{a.email_address}</option>
                    ))}
                  </select>
                </div>
              ) : (
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '10px 0', borderBottom: '1px solid rgba(255,255,255,0.06)' }}>
                  <span style={{ fontSize: 12, color: '#475569', width: 52, flexShrink: 0 }}>From</span>
                  <span style={{ fontSize: 13.5, color: '#64748b' }}>{from}</span>
                </div>
              )}

              {/* To */}
              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <span style={{ fontSize: 12, color: '#475569', width: 52, flexShrink: 0 }}>To</span>
                <input value={to} onChange={e => setTo(e.target.value)} placeholder="recipient@example.com"
                  style={{ ...inputStyle, flex: 1, borderBottom: 'none', padding: '10px 0' }} />
                <button onClick={() => setShowCc(s => !s)}
                  style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#334155', fontSize: 11, fontFamily: 'inherit', display: 'flex', alignItems: 'center', gap: 2 }}>
                  Cc <ChevronDown size={10} style={{ transform: showCc ? 'rotate(180deg)' : 'none' }} />
                </button>
                <div style={{ width: '100%', position: 'absolute', left: 0, right: 0, marginTop: 40, height: 1, background: 'rgba(255,255,255,0.06)' }} />
              </div>
              <div style={{ borderBottom: '1px solid rgba(255,255,255,0.06)' }} />

              {/* CC */}
              <AnimatePresence>
                {showCc && (
                  <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: 'auto', opacity: 1 }} exit={{ height: 0, opacity: 0 }}
                    style={{ overflow: 'hidden' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                      <span style={{ fontSize: 12, color: '#475569', width: 52, flexShrink: 0 }}>Cc</span>
                      <input value={cc} onChange={e => setCc(e.target.value)} placeholder="cc@example.com"
                        style={{ ...inputStyle, flex: 1 }} />
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>

              {/* Subject */}
              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <span style={{ fontSize: 12, color: '#475569', width: 52, flexShrink: 0 }}>Subject</span>
                <input value={subject} onChange={e => setSubject(e.target.value)} placeholder="Subject"
                  style={{ ...inputStyle, flex: 1, fontWeight: subject ? 600 : 400 }} />
              </div>

              {/* Body */}
              <textarea
                ref={bodyRef}
                value={body} onChange={e => setBody(e.target.value)}
                placeholder="Write your message…"
                style={{
                  width: '100%', minHeight: 180, padding: '14px 0', background: 'none', border: 'none',
                  outline: 'none', color: '#cbd5e1', fontSize: 13.5, lineHeight: 1.65,
                  fontFamily: 'inherit', resize: 'vertical', boxSizing: 'border-box',
                }}
              />

              {/* Footer */}
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '12px 0', borderTop: '1px solid rgba(255,255,255,0.06)' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <motion.button
                    onClick={handleSend}
                    disabled={sending || !to.trim() || !subject.trim()}
                    whileHover={!sending && to && subject ? { scale: 1.02, boxShadow: '0 4px 20px rgba(239,68,68,0.4)' } : {}}
                    whileTap={{ scale: 0.97 }}
                    style={{
                      display: 'flex', alignItems: 'center', gap: 7, padding: '9px 20px',
                      borderRadius: 12, border: 'none', cursor: sending || !to || !subject ? 'not-allowed' : 'pointer',
                      background: sent
                        ? 'rgba(16,185,129,0.15)'
                        : 'linear-gradient(135deg,#ef4444,#f97316)',
                      color: sent ? '#34d399' : '#fff',
                      fontSize: 13.5, fontWeight: 700, fontFamily: 'inherit',
                      opacity: !to || !subject ? 0.5 : 1,
                      transition: 'all 0.2s',
                    }}
                  >
                    {sent ? (
                      <><span>✓</span> Sent!</>
                    ) : sending ? (
                      <><motion.span animate={{ rotate: 360 }} transition={{ duration: 0.8, repeat: Infinity, ease: 'linear' }}>↻</motion.span> Sending…</>
                    ) : (
                      <><Send size={13} /> Send</>
                    )}
                  </motion.button>
                </div>

                <motion.button
                  whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.9 }}
                  onClick={handleDiscard}
                  style={{ width: 32, height: 32, borderRadius: 9, background: 'rgba(239,68,68,0.06)', border: '1px solid rgba(239,68,68,0.15)', cursor: 'pointer', color: '#f87171', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                  title="Discard"
                >
                  <Trash2 size={13} />
                </motion.button>
              </div>
            </div>
          )}
        </motion.div>
      )}
    </AnimatePresence>
  )
}
