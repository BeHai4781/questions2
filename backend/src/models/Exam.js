import mongoose from 'mongoose';

const examSchema = new mongoose.Schema(
  {
    title: {
      type: String,
      required: [true, 'Exam title is required'],
      trim: true,
      maxlength: [255, 'Title cannot exceed 255 characters'],
    },
    description: {
      type: String,
      trim: true,
    },
    duration: {
      type: Number,
      default: null,
      min: [1, 'Duration must be at least 1 minute'],
    },
    class: {
      type: String,
      trim: true,
    },
    type: {
      type: String,
      enum: ['exam', 'practice'],
      required: true,
      default: 'exam',
    },
    creator_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    questions: [
      {
        question_id: {
          type: mongoose.Schema.Types.ObjectId,
          ref: 'Question',
          required: true,
        },
        order: {
          type: Number,
          default: 0,
        },
        score: {
          type: Number,
          default: 1.0,
          min: [0, 'Score cannot be negative'],
        },
      },
    ],
    shuffle_questions: {
      type: Boolean,
      default: false,
    },
    shuffle_answers: {
      type: Boolean,
      default: false,
    },
    is_published: {
      type: Boolean,
      default: false,
    },
    total_questions: {
      type: Number,
      default: 0,
    },
    total_score: {
      type: Number,
      default: 0,
    },
  },
  {
    timestamps: true,
  }
);

// Tính tổng câu hỏi và điểm trước khi lưu
examSchema.pre('save', function (next) {
  this.total_questions = this.questions.length;
  this.total_score = this.questions.reduce((sum, q) => sum + q.score, 0);
  next();
});

// Transform để match với frontend structure
examSchema.set('toJSON', {
  virtuals: true,
  transform: function (doc, ret) {
    ret.id = ret._id;
    // Format created_at
    if (ret.createdAt) {
      ret.created_at = ret.createdAt.toISOString().split('T')[0];
    }
    // Creator sẽ được populate và lấy username
    // Nếu đã populate, creator_id sẽ là object User
    if (ret.creator_id && typeof ret.creator_id === 'object' && ret.creator_id.username) {
      ret.creator = ret.creator_id.username;
    } else if (typeof ret.creator_id === 'string') {
      // Nếu chưa populate, chỉ có ObjectId string, sẽ cần populate ở controller
      ret.creator = null; // Sẽ được populate ở controller
    }
    delete ret._id;
    delete ret.__v;
    delete ret.updatedAt;
    return ret;
  },
});

// Index
examSchema.index({ creator_id: 1 });
examSchema.index({ class: 1 });
examSchema.index({ is_published: 1, type: 1 });

const Exam = mongoose.model('Exam', examSchema);

export default Exam;

