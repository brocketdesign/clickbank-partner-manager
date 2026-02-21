import { ObjectId } from "mongodb";

// ---- Domains ----
export interface Domain {
  _id?: ObjectId;
  domain_name: string;
  is_active: boolean;
  created_at: Date;
  updated_at: Date;
}

// ---- Partners (redirect rules engine) ----
export interface Partner {
  _id?: ObjectId;
  aff_id: string;
  partner_name: string;
  is_active: boolean;
  created_at: Date;
  updated_at: Date;
}

// ---- Offers ----
export interface Offer {
  _id?: ObjectId;
  offer_name: string;
  clickbank_vendor: string;
  clickbank_hoplink: string;
  is_active: boolean;
  created_at: Date;
  updated_at: Date;
}

// ---- Redirect Rules ----
export interface RedirectRule {
  _id?: ObjectId;
  rule_name: string;
  rule_type: "global" | "domain" | "partner";
  domain_id?: ObjectId | null;
  partner_id?: ObjectId | null;
  offer_id: ObjectId;
  is_paused: boolean;
  priority: number;
  created_at: Date;
  updated_at: Date;
}

// ---- Partner Applications ----
export interface PartnerApplication {
  _id?: ObjectId;
  name: string;
  email: string;
  blog_url: string;
  traffic_estimate: string;
  notes?: string;
  consent: boolean;
  status: "pending" | "approved" | "rejected" | "info_requested";
  domain_verification_status: "unchecked" | "verified" | "failed";
  domain_verified: boolean;
  created_at: Date;
  ip_address: string;
  messages?: ApplicationMessage[];
}

export interface ApplicationMessage {
  _id?: ObjectId;
  message_type: "reject" | "request_info" | "approve" | "payout_notification";
  message_text: string;
  created_at: Date;
}

// ---- Partners New (snippet/popup system) ----
export interface PartnerNew {
  _id?: ObjectId;
  partner_id_public: string; // UUID
  name: string;
  email: string;
  blog_url: string;
  allowed_domains?: string;
  status: "pending" | "approved" | "rejected";
  domain_verification_status: string;
  created_at: Date;
  approved_at?: Date;
  notes?: string;
  ip_address: string;
}

// ---- Creatives ----
export interface Creative {
  _id?: ObjectId;
  partner_id: ObjectId;
  name: string;
  type: "banner" | "text" | "native";
  destination_hoplink: string;
  weight: number;
  html?: string;
  active: boolean;
  created_at: Date;
  updated_at: Date;
}

// ---- Click Logs ----
export interface ClickLog {
  _id?: ObjectId;
  domain_id?: ObjectId | null;
  partner_id?: ObjectId | null;
  offer_id?: ObjectId | null;
  rule_id?: ObjectId | null;
  ip_address: string;
  user_agent: string;
  referer: string;
  redirect_url: string;
  clicked_at: Date;
}

// ---- Clicks (attribution) ----
export interface Click {
  _id?: ObjectId;
  partner_id: ObjectId;
  creative_id?: ObjectId | null;
  click_id: string; // UUID
  ip_hash: string;
  ua_hash: string;
  referrer?: string;
  ts: Date;
}

// ---- Impressions ----
export interface Impression {
  _id?: ObjectId;
  partner_id: ObjectId;
  creative_id?: ObjectId | null;
  ip_hash: string;
  ua_hash: string;
  ts: Date;
}

// ---- Conversions ----
export interface Conversion {
  _id?: ObjectId;
  click_id: string;
  external_id: string;
  amount: number;
  ts: Date;
}

// ---- Payouts ----
export interface Payout {
  _id?: ObjectId;
  partner_id: ObjectId;
  month: string; // "YYYY-MM"
  clicks: number;
  amount: number;
  badge: "bronze" | "silver" | "gold";
  status: "pending" | "paid";
  paid_at?: Date;
  created_at: Date;
}

// ---- Admin Users ----
export interface AdminUser {
  _id?: ObjectId;
  username: string;
  password_hash: string;
  created_at: Date;
}
