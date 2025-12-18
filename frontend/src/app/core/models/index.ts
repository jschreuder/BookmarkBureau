// Dashboard models
export interface Dashboard {
  dashboard_id: string;
  title: string;
  description?: string;
  icon?: string;
  created_at: string;
  updated_at: string;
}

// Category models
export interface Category {
  category_id: string;
  dashboard_id: string;
  title: string;
  color?: string;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

export interface CategoryWithLinks extends Category {
  links: Link[];
}

// Link models
export interface Link {
  link_id: string;
  url: string;
  title: string;
  description: string;
  icon?: string;
  created_at: string;
  updated_at: string;
  tags?: Tag[];
}

// Tag models
export interface Tag {
  tag_name: string;
  color?: string;
}

// Favorite models
export interface Favorite {
  dashboard_id: string;
  link_id: string;
  sort_order: number;
  created_at: string;
}

// Full dashboard view
export interface FullDashboard {
  dashboard: Dashboard;
  categories: CategoryWithLinks[];
  favorites: Link[];
}

// API Response wrapper
export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
}
