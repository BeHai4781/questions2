import mongoose from 'mongoose';
import dotenv from 'dotenv';
import User from '../models/User.js';
import Exam from '../models/Exam.js';
import Question from '../models/Question.js';
import connectDB from '../config/database.js';

// Load environment variables
dotenv.config();

// Seed data - Match với dữ liệu mẫu frontend
const seedUsers = [
  {
    username: 'nguyenvana',
    email: 'a@email.com',
    password: '123456',
    fullname: 'Nguyễn Văn A',
    phone: '0123456789',
    role: 'student',
    status: 'active',
  },
  {
    username: 'tranthib',
    email: 'b@email.com',
    password: '123456',
    fullname: 'Trần Thị B',
    phone: '0987654321',
    role: 'teacher',
    status: 'banned',
  },
  {
    username: 'levanc',
    email: 'c@email.com',
    password: '123456',
    fullname: 'Lê Văn C',
    phone: '0111222333',
    role: 'admin',
    status: 'active',
  },
  {
    username: 'phamthid',
    email: 'd@email.com',
    password: '123456',
    fullname: 'Phạm Thị D',
    phone: '0222333444',
    role: 'student',
    status: 'banned',
  },
  {
    username: 'hoangvane',
    email: 'e@email.com',
    password: '123456',
    fullname: 'Hoàng Văn E',
    phone: '0333444555',
    role: 'teacher',
    status: 'active',
  },

];

// Seed questions - Match với dữ liệu mẫu frontend
const seedQuestions = [
  {
    content: 'Giải phương trình bậc hai: x^2 - 5x + 6 = 0.',
    subject: 'Toán',
    class: '12',
    difficulty: 'medium',
    type: 'multiple_choice',
    answers: [
      { text: 'x = 2 và x = 3', is_correct: true, order: 1 },
      { text: 'x = 1 và x = 6', is_correct: false, order: 2 },
      { text: 'x = -2 và x = -3', is_correct: false, order: 3 },
      { text: 'x = 0 và x = 5', is_correct: false, order: 4 },
    ],
    is_public: true,
  },
  {
    content: 'Phân tích nhân vật Tràng trong Vợ nhặt.',
    subject: 'Văn',
    class: '11',
    difficulty: 'hard',
    type: 'essay',
    answers: [],
    is_public: true,
  },
  {
    content: 'Write an essay about your favorite hobby.',
    subject: 'Anh',
    class: '10',
    difficulty: 'medium',
    type: 'essay',
    answers: [],
    is_public: true,
  },
  {
    content: 'Trình bày định luật II Newton và lấy ví dụ minh hoạ.',
    subject: 'Lý',
    class: '12',
    difficulty: 'medium',
    type: 'essay',
    answers: [],
    is_public: true,
  },
  {
    content: 'Tính số mol của 22,4 lít khí oxi ở đktc.',
    subject: 'Hoá',
    class: '11',
    difficulty: 'easy',
    type: 'multiple_choice',
    answers: [
      { text: '1 mol', is_correct: true, order: 1 },
      { text: '2 mol', is_correct: false, order: 2 },
      { text: '0.5 mol', is_correct: false, order: 3 },
      { text: '22.4 mol', is_correct: false, order: 4 },
    ],
    is_public: true,
  },
  // Thêm một số câu hỏi trắc nghiệm để có đủ data
  {
    content: '2 + 2 = ?',
    subject: 'Toán',
    class: '10',
    difficulty: 'easy',
    type: 'multiple_choice',
    answers: [
      { text: '3', is_correct: false, order: 1 },
      { text: '4', is_correct: true, order: 2 },
      { text: '5', is_correct: false, order: 3 },
      { text: '6', is_correct: false, order: 4 },
    ],
    is_public: true,
  },
  {
    content: '5 x 3 = ?',
    subject: 'Toán',
    class: '10',
    difficulty: 'easy',
    type: 'multiple_choice',
    answers: [
      { text: '15', is_correct: true, order: 1 },
      { text: '10', is_correct: false, order: 2 },
      { text: '8', is_correct: false, order: 3 },
      { text: '20', is_correct: false, order: 4 },
    ],
    is_public: true,
  },
];

// Seed exams - Match với dữ liệu mẫu frontend
const seedExams = [
  {
    title: 'Đề thi Toán',
    description: 'Đề thi môn Toán',
    duration: 60,
    class: 'Lớp 10',
    type: 'exam',
    shuffle_questions: false,
    shuffle_answers: false,
    is_published: true,
  },
  {
    title: 'Đề thi Văn',
    description: 'Đề thi môn Văn',
    duration: 45,
    class: 'Lớp 11',
    type: 'exam',
    shuffle_questions: false,
    shuffle_answers: false,
    is_published: true,
  },
  {
    title: 'Đề thi Anh',
    description: 'Đề thi môn Anh',
    duration: 50,
    class: 'Lớp 12',
    type: 'exam',
    shuffle_questions: false,
    shuffle_answers: false,
    is_published: true,
  },
];

// Main seed function
const seedDatabase = async () => {
  try {
    console.log('🔄 Connecting to MongoDB...');
    await connectDB();

    console.log('🗑️  Clearing existing data...');
    
    // Xóa dữ liệu cũ (optional - có thể comment nếu muốn giữ lại)
    await User.deleteMany({});
    await Question.deleteMany({});
    await Exam.deleteMany({});
    
    console.log('✅ Database cleared');

    console.log('👥 Creating users...');
    const createdUsers = await User.insertMany(seedUsers);
    console.log(`✅ Created ${createdUsers.length} users`);

    // Lấy teacher IDs để gán cho questions và exams
    const teachers = createdUsers.filter((u) => u.role === 'teacher');
    const teacher1 = teachers.find((u) => u.username === 'teacher1') || teachers[0];
    const teacher2 = teachers.find((u) => u.username === 'tranthib') || teachers[0];
    const teacher3 = teachers.find((u) => u.username === 'teacher2') || teachers[0];
    const teacher1Id = teacher1._id;
    const teacher2Id = teacher2._id;
    const teacher3Id = teacher3._id;

    console.log('❓ Creating questions...');
    const questionsWithCreator = seedQuestions.map((q) => ({
      ...q,
      creator_id: teacherId,
    }));
    const createdQuestions = await Question.insertMany(questionsWithCreator);
    console.log(`✅ Created ${createdQuestions.length} questions`);

    console.log('📝 Creating exams...');
    const examsWithCreator = seedExams.map((exam, index) => {
      // Gán creator khác nhau cho mỗi exam
      let creatorId = teacher1Id;
      if (index === 1) creatorId = teacher2Id;
      if (index === 2) creatorId = teacher3Id;
      
      // Gán questions cho exam (mỗi exam có ít nhất 3-5 questions)
      const startIdx = index * 2;
      const endIdx = Math.min(startIdx + 5, createdQuestions.length);
      const examQuestions = createdQuestions.slice(startIdx, endIdx);
      
      return {
        ...exam,
        creator_id: creatorId,
        questions: examQuestions.map((q, qIndex) => ({
          question_id: q._id,
          order: qIndex + 1,
          score: 1.0,
        })),
      };
    });
    const createdExams = await Exam.insertMany(examsWithCreator);
    console.log(`✅ Created ${createdExams.length} exams`);

    console.log('\n✨ Seed data created successfully!');
    console.log('\n📊 Summary:');
    console.log(`   - Users: ${createdUsers.length}`);
    console.log(`   - Questions: ${createdQuestions.length}`);
    console.log(`   - Exams: ${createdExams.length}`);
    console.log('\n🔑 Default credentials:');
    console.log('   Admin:');
    console.log('     Username: admin');
    console.log('     Password: admin123');
    console.log('   Teacher:');
    console.log('     Username: teacher1');
    console.log('     Password: teacher123');
    console.log('   Student:');
    console.log('     Username: student1');
    console.log('     Password: student123');

    process.exit(0);
  } catch (error) {
    console.error('❌ Error seeding database:', error);
    process.exit(1);
  }
};

// Run seed
seedDatabase();
