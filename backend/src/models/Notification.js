import mongoose from 'mongoose';

const notificationSchema = new mongoose.Schema(
  {
    user_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    type: {
      type: String,
      required: true,
      // Ví dụ: 'new_user', 'new_exam', 'new_contact', 'exam_result', etc.
    },
    title: {
      type: String,
      trim: true,
    },
    content: {
      type: String,
      required: true,
      trim: true,
    },
    link: {
      type: String,
      default: null,
      // Link để điều hướng khi click vào notification
    },
    is_read: {
      type: Boolean,
      default: false,
    },
    read_at: {
      type: Date,
      default: null,
    },
  },
  {
    timestamps: true,
  }
);

// Index
notificationSchema.index({ user_id: 1, is_read: 1 });
notificationSchema.index({ user_id: 1, created_at: -1 });

const Notification = mongoose.model('Notification', notificationSchema);

export default Notification;

