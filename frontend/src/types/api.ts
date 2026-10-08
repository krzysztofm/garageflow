export interface User {
  id: number
  name: string
  email: string
  role: 'employee' | 'manager' | 'admin'
}

export interface ApiResponse<T> {
  data: T
}

export interface DashboardData {
  date: string
  counts: {
    employees: number
    pending_leaves: number
    planned_trips: number
    absent_today: number
  }
  absent_employees: {
    id: number
    name: string
    department: string
    position: string
  }[]
}

export interface Employee {
  id: number
  user_id: number
  user: User
  manager_id: number | null
  manager: User | null
  department: string
  position: string
  can_update: boolean
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
}

export type LeaveStatus = 'pending' | 'approved' | 'rejected'

export interface LeaveRequest {
  id: number
  employee_id: number
  employee: Employee
  start_date: string
  end_date: string
  reason: string | null
  status: LeaveStatus
  reviewed_by: number | null
  reviewed_at: string | null
  reviewer: User | null
  can_review: boolean
}

export type TripStatus = 'planned' | 'completed' | 'cancelled'

export interface BusinessTrip {
  id: number
  employee_id: number
  employee: Employee
  start_date: string
  end_date: string
  destination: string
  description: string | null
  status: TripStatus
  can_update: boolean
}
