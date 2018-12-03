CREATE TABLE IF NOT EXISTS global_settings (
  id int(10) unsigned NOT NULL auto_increment,
  name text NULL,
  data1 longtext NULL,
  data2 longtext NULL,
  data3 longtext NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO global_settings (id, name, data1, data2, data3) VALUES
(1, 'app', '', '', ''),
(2, 'cache_path', '', '', ''),
(3, 'mediamanager_path', '', '', ''),
(4, 'mediamanager_thumbnail', '', '', ''),
(5, 'projects_path', '', '', ''),
(6, 'projects_thumbnail', '100', '0', ''),
(7, 'projects_filethumbnail', '100', '0', ''),
(8, 'projects_intelliscaling', '1', '', ''),
(9, 'projects_hideSections', '0', '', ''),
(10, 'projects_hideFileInfo', '0', '', ''),
(11, 'projects_thumbnailIntelliScaling', '1', '', ''),
(12, 'blog_path', '', '', ''),
(13, 'blog_thumbnail', '0', '', ''),
(14, 'blog_intelliscaling', '1', '', ''),
(15, 'resizeProjThumb', '1', '', ''),
(16, 'site', '', '', ''),
(17, 'site_theme', 'aplonis', '', ''),
(18, 'index_page', '1', '', ''),
(19, 'maintenanceMode', '0', '', ''),
(20, 'clean_urls', '0', '', ''),
(21, 'projects_fullsizeimg', '', '1', '0'),
(22, 'slideshow_opts', '', '', '');

CREATE TABLE IF NOT EXISTS pages (
  id int(11) NOT NULL auto_increment,
  name text NULL,
  slug text NULL,
  url text NULL,
  text text NULL,
  content_type tinytext NULL,
  content_options text NULL,
  hidden int(11) NULL,
  parent int(11) NULL,
  pos int(11) NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO pages (id, name, slug, url, text, content_options, content_type, hidden, parent, pos) VALUES
(1, 'Projects', 'projects', '', '', '', 'projects', 0, 0, 1),
(2, 'News', 'news', '', '', '', 'blog', 0, 0, 2),
(3, 'About', 'about', '', 'About you.', '', 'none', 0, 0, 3),
(4, 'Contact', 'contact', '', 'Your contact info.', '', 'none', 0, 0, 4);

CREATE TABLE IF NOT EXISTS projects (
  id int(11) NOT NULL auto_increment,
  title text NULL,
  slug text NULL,
  description text NULL,
  date int(11) NULL,
  section int(11) NULL,
  pos int(11) NULL,
  flow text NULL,
  thumbnail text NULL,
  publish int(11) NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO projects (id, title, slug, description, date, section, pos, flow, thumbnail, publish) VALUES
(1, 'First Project', 'first-project', '', 1288625607, 1, 1, 'textblock1,group1:one-by-one,group3:pop,group2:slideshow', 'blue.project.jpg', 1);

CREATE TABLE IF NOT EXISTS projects_to_tags (
  id int(11) NOT NULL auto_increment,
  tag text NULL,
  projectid int(11) NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS project_files (
  id int(11) NOT NULL auto_increment,
  title text NULL,
  caption text NULL,
  file text NULL,
  thumbnail text NULL,
  width int(11) NULL,
  height int(11) NULL,
  project_id int(11) NULL,
  pos int(11) NULL,
  type text NULL,
  filegroup int(11) NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO project_files (id, title, caption, file, thumbnail, width, height, project_id, pos, type, filegroup) VALUES
(1, '', 'This demo project demonstrates the use of textblocks and groups, and utilises all display types (one by one, pop and slideshow).', '', '', 0, 0, 1, 0, 'text', 0),
(2, 'Secretary Blue', 'I quite like this blue.', 'blue.jpg', 'blue.thumb.jpg', 400, 600, 1, 1, 'image', 1),
(3, '', '', 'green.jpg', 'green.thumb.jpg', 400, 600, 1, 1, 'image', 3),
(4, '', '', 'purple.jpg', 'purple.thumb.jpg', 400, 600, 1, 2, 'image', 2),
(5, '', '', 'red.jpg', 'red.thumb.jpg', 400, 600, 1, 2, 'image', 3),
(6, '', '', 'yellow.jpg', 'yellow.thumb.jpg', 400, 600, 1, 1, 'image', 2);

CREATE TABLE IF NOT EXISTS project_sections (
  id int(11) NOT NULL auto_increment,
  name text NULL,
  slug text NULL,
  pos int(11) NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO project_sections (id, name, slug, pos) VALUES
(1, 'Latest Work', 'latest', 1);

CREATE TABLE IF NOT EXISTS users (
  id int(10) unsigned NOT NULL auto_increment,
  username text NULL,
  password text NULL,
  display_name text NULL,
  email text NULL,
  level_id int(11) NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS secretary_blog (
  id int(10) unsigned NOT NULL auto_increment,
  slug text NULL,
  date int(11) NULL,
  title text NULL,
  post longtext NULL,
  image text NULL,
  status int(10) NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO secretary_blog (id, slug, date, title, post, image, status) VALUES
(1, 'new-website', '1288625607', 'New Website', 'My new Secretary powered website is up!', '', 1 );
