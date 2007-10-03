BEGIN;
create temporary view album_tracklen as select albumjoin.album,albumjoin.sequence,length from track join albumjoin on (track.id = albumjoin.track) order by albumjoin.sequence;
create temporary view album_trackponies as select album,array_accum(length) as tracklist from album_tracklen group by album;
drop table if exists album_tracklist cascade;
create table album_tracklist as select album,tracklist,array_upper(tracklist, 1) as track_count from album_trackponies;


ALTER TABLE album_tracklist
  ADD CONSTRAINT album_tracklist_pkey PRIMARY KEY(album);


-- DROP INDEX atltc;

CREATE INDEX atltc
  ON album_tracklist
  (track_count);

COMMIT;

begin;
drop table if exists dupscan_cache cascade;
create table dupscan_cache as
select track_count,simple_cd_hash(tracklist) as hash, tracklist, album
from album_tracklist
where tracklist!='{0}'
AND sum_array(tracklist)!=0
AND track_count > 4
order by track_count,hash;
create index ponies on dupscan_cache(track_count);
commit;