import mongoose from 'mongoose';

const questionSchema = new mongoose.Schema(
  {
    content: {
      type: String,
      required: [true, 'Question content is required'],
      trim: true,
    },
    image_url: {
      type: String,
      default: null,
    },
    subject: {
      type: String,
      trim: true,
    },
    class: {
      type: String,
      trim: true,
    },
    difficulty: {
      type: String,
      enum: ['easy', 'medium', 'hard', 'very_hard'],
      default: 'medium',
    },
    type: {
      type: String,
      enum: ['multiple_choice', 'essay', 'mixed'],
      default: 'multiple_choice',
    },
    creator_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    answers: [
      {
        text: {
          type: String,
          required: true,
        },
        is_correct: {
          type: Boolean,
          default: false,
        },
        order: {
          type: Number,
          default: 0,
        },
      },
    ],
    is_public: {
      type: Boolean,
      default: false,
    },
    usage_count: {
      type: Number,
      default: 0,
    },
  },
  {
    timestamps: true,
  }
);

// Virtual fields để match với frontend
questionSchema.virtual('subject_name').get(function () {
  return this.subject;
});

questionSchema.virtual('class_name').get(function () {
  return this.class;
});

questionSchema.virtual('question').get(function () {
  return this.content;
});

// Đảm bảo virtual fields được include khi convert sang JSON
questionSchema.set('toJSON', {
  virtuals: true,
  transform: function (doc, ret) {
    ret.id = ret._id;
    ret.subject_name = ret.subject;
    ret.class_name = ret.class;
    ret.question = ret.content;
    delete ret._id;
    delete ret.__v;
    return ret;
  },
});

// Index tìm kiếm nhanh
questionSchema.index({ subject: 1, class: 1 });
questionSchema.index({ creator_id: 1 });
questionSchema.index({ is_public: 1 });

const Question = mongoose.model('Question', questionSchema);

export default Question;

