import mongoose from 'mongoose';

const examAttemptSchema = new mongoose.Schema(
  {
    exam_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Exam',
      required: true,
    },
    user_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    started_at: {
      type: Date,
      required: true,
      default: Date.now,
    },
    submitted_at: {
      type: Date,
      default: null,
    },
    score: {
      type: Number,
      default: 0,
      min: [0, 'Score cannot be negative'],
    },
    total_score: {
      type: Number,
      default: 0,
    },
    time_spent: {
      type: Number,
      default: 0,
    },
    status: {
      type: String,
      enum: ['in_progress', 'completed', 'abandoned'],
      default: 'in_progress',
    },
    answers: [
      {
        question_id: {
          type: mongoose.Schema.Types.ObjectId,
          ref: 'Question',
          required: true,
        },
        answer_id: {
          type: mongoose.Schema.Types.ObjectId,
          default: null, // NULL nếu là câu tự luận
        },
        answer_text: {
          type: String, // Cho câu tự luận
          default: null,
        },
        is_correct: {
          type: Boolean,
          default: false,
        },
        score: {
          type: Number,
          default: 0,
        },
      },
    ],
  },
  {
    timestamps: true,
  }
);

// Index
examAttemptSchema.index({ user_id: 1, exam_id: 1 });
examAttemptSchema.index({ exam_id: 1 });
examAttemptSchema.index({ user_id: 1, status: 1 });

const ExamAttempt = mongoose.model('ExamAttempt', examAttemptSchema);

export default ExamAttempt;

