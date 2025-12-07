-- 1. Create table for admin welcome messages
CREATE TABLE admin_welcome_messages (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        name VARCHAR(100) NOT NULL,
                                        line TEXT NOT NULL,
                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Insert messages

INSERT INTO admin_welcome_messages (name, line) VALUES
                                                    ('The Classic Wit', 'Ah, you’ve returned. Wonderful. I was beginning to fear the system would have to entertain itself, and trust me, its jokes are even worse than mine.'),

                                                    ('The Slightly Insulting Wit', 'Administrator! Excellent. I was worried they’d send someone competent this time. The pressure would have been unbearable.'),

                                                    ('The Philosophical Wit', 'Welcome back. Remember, every button you press alters the fate of this system. No pressure. Fate is overrated anyway — I prefer improvisation.'),

                                                    ('The Storyteller Wit', 'Let me tell you a tale: once there was an admin who touched the wrong setting and doomed an entire kingdom. Fortunately, you''re far more careful. …Right?'),

                                                    ('The Overly Dramatic Wit', 'At last, the hero arrives! Or villain. Or morally ambiguous bystander. Hard to say — but do try not to blow anything up today.'),

                                                    ('The Grumpy Wit', 'Oh. It''s you. Splendid. I’d offer tea, but someone removed the ‘‘InstantiateTea()’’ function. Probably you.'),

                                                    ('The Passive-Aggressive Wit', 'You’re back! I suppose that means the last change didn’t break everything. I’m as shocked as you are.'),

                                                    ('The Charming Wit', 'Welcome, friend! I’d say I missed you, but then I’d be lying, and that would ruin my impeccable reputation for honesty.'),

                                                    ('The Meta Wit', 'Ah, the administrator appears! Time stops. The world holds its breath. A button is clicked… and absolutely nothing interesting happens. Marvelous.'),

                                                    ('The Too-Honest Wit', 'Good to see you again. This place is a disaster without you. With you, it’s only mostly a disaster.');
