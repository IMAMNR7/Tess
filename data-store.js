const STORAGE_KEY = 'himasi-portal-data-v2';

const DEFAULT_DATA = {
  news: [
    {
      id: crypto.randomUUID(),
      title: 'Rapat Koordinasi Awal Semester',
      content: 'Sinkronisasi agenda kepengurusan dan timeline seluruh divisi untuk satu semester.',
    },
    {
      id: crypto.randomUUID(),
      title: 'Kunjungan Industri SI',
      content: 'Program observasi langsung ke perusahaan teknologi untuk menambah wawasan anggota.',
    },
  ],
  proker: [
    {
      id: crypto.randomUUID(),
      title: 'SI Tech Class',
      description: 'Pelatihan web, UI/UX, data analytics, dan manajemen produk tiap bulan.',
    },
    {
      id: crypto.randomUUID(),
      title: 'Mentoring Akademik',
      description: 'Pendampingan mata kuliah inti dan persiapan magang untuk mahasiswa SI.',
    },
  ],
  members: [
    {
      id: crypto.randomUUID(),
      name: 'Nadia Putri',
      role: 'Ketua HIMASI',
      division: 'BPH',
      photo: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=700&q=80',
    },
    {
      id: crypto.randomUUID(),
      name: 'Rizky Maulana',
      role: 'Koordinator Proker',
      division: 'Akademik',
      photo: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=700&q=80',
    },
  ],
};

function loadPortalData() {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(DEFAULT_DATA));
    return structuredClone(DEFAULT_DATA);
  }

  try {
    const parsed = JSON.parse(raw);
    return {
      news: parsed.news || [],
      proker: parsed.proker || [],
      members: parsed.members || [],
    };
  } catch {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(DEFAULT_DATA));
    return structuredClone(DEFAULT_DATA);
  }
}

function savePortalData(data) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}
